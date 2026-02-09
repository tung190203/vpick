<?php

namespace App\Services\Club;

use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionSourceType;
use App\Enums\ClubWalletTransactionStatus;
use App\Models\Club\Club;
use App\Models\Club\ClubExpense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ClubExpenseService
{
    public function getExpenses(Club $club, array $filters): LengthAwarePaginator
    {
        $query = $club->expenses()->with(['spender', 'walletTransaction']);

        if (!empty($filters['spent_by'])) {
            $query->where('spent_by', $filters['spent_by']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('spent_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('spent_at', '<=', $filters['date_to']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('spent_at', 'desc')->paginate($perPage);
    }

    public function createExpense(Club $club, array $data, int $userId): ClubExpense
    {
        if (!$club->canManageFinance($userId)) {
            throw new \Exception('Chỉ admin/manager/treasurer mới có quyền tạo chi phí');
        }

        $description = (string) ($data['description'] ?? $data['title'] ?? '');

        return DB::transaction(function () use ($club, $data, $userId, $description) {
            $expense = ClubExpense::create([
                'club_id' => $club->id,
                'title' => $description,
                'amount' => $data['amount'],
                'spent_by' => $userId,
                'spent_at' => $data['spent_at'] ?? now(),
                'note' => $data['note'] ?? null,
            ]);

            $mainWallet = $club->mainWallet;
            if ($mainWallet) {
                $transaction = $mainWallet->transactions()->create([
                    'direction' => ClubWalletTransactionDirection::Out,
                    'amount' => $data['amount'],
                    'source_type' => ClubWalletTransactionSourceType::Expense,
                    'source_id' => $expense->id,
                    'payment_method' => $data['payment_method'],
                    'status' => ClubWalletTransactionStatus::Pending,
                    'reference_code' => $data['reference_code'] ?? null,
                    'description' => $description,
                    'created_by' => $userId,
                ]);

                $expense->update(['wallet_transaction_id' => $transaction->id]);
            }

            return $expense;
        });
    }

    public function updateExpense(ClubExpense $expense, array $data, int $userId): ClubExpense
    {
        $club = $expense->club;
        if (!$club->canManageFinance($userId)) {
            throw new \Exception('Chỉ admin/manager/treasurer mới có quyền cập nhật');
        }

        if (isset($data['description'])) {
            $description = (string) $data['description'];
            $data['title'] = $description;
            if ($expense->walletTransaction) {
                $expense->walletTransaction->update(['description' => $description]);
            }
        }
        unset($data['description']);

        $expense->update($data);
        return $expense;
    }

    public function deleteExpense(ClubExpense $expense, int $userId): void
    {
        $club = $expense->club;
        if (!$club->canManageFinance($userId)) {
            throw new \Exception('Chỉ admin/manager/treasurer mới có quyền xóa');
        }

        $expense->delete();
    }

    public function getStatistics(Club $club, array $filters): array
    {
        $query = $club->expenses();

        if (!empty($filters['date_from'])) {
            $query->whereDate('spent_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('spent_at', '<=', $filters['date_to']);
        }

        return [
            'total_expenses' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'by_month' => $query->selectRaw('DATE_FORMAT(spent_at, "%Y-%m") as month, SUM(amount) as amount')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => $item->month,
                        'amount' => (float) $item->amount,
                    ];
                }),
        ];
    }
}
