<?php

namespace App\Services\Club;

use App\Enums\ClubFundCollectionStatus;
use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionStatus;
use App\Models\Club\Club;
use App\Models\Club\ClubWallet;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ClubWalletService
{
    public function getWallets(Club $club, array $filters): Collection
    {
        $query = $club->wallets();

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->get();
    }

    public function createWallet(Club $club, array $data): ClubWallet
    {
        $exists = $club->wallets()
            ->where('type', $data['type'])
            ->where('currency', $data['currency'] ?? 'VND')
            ->exists();

        if ($exists) {
            throw new \Exception('Ví loại này đã tồn tại');
        }

        return ClubWallet::create([
            'club_id' => $club->id,
            'type' => $data['type'],
            'currency' => $data['currency'] ?? 'VND',
            'qr_code_url' => $data['qr_code_url'] ?? null,
        ]);
    }

    public function updateWallet(ClubWallet $wallet, array $data): ClubWallet
    {
        $wallet->update($data);
        return $wallet;
    }

    public function deleteWallet(ClubWallet $wallet): void
    {
        if ($wallet->transactions()->exists()) {
            throw new \Exception('Không thể xóa ví vì có giao dịch');
        }

        $wallet->delete();
    }

    public function getTransactions(ClubWallet $wallet, array $filters): LengthAwarePaginator
    {
        $query = $wallet->transactions()->with(['creator', 'confirmer']);

        if (!empty($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Tổng quan quỹ: tổng quỹ hiện tại (balance), thu tháng này, chi tháng này.
     * - balance: tổng từ mọi giao dịch đã xác nhận (In - Out).
     * - total_income / total_expense: mặc định theo tháng hiện tại (confirmed_at trong tháng).
     * - Nếu FE gửi date_from, date_to thì lọc theo khoảng đó (theo confirmed_at).
     */
    public function getFundOverview(Club $club, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $mainWallet = $club->mainWallet;

        if (!$mainWallet) {
            return [
                'balance' => 0,
                'total_income' => 0,
                'total_expense' => 0,
                'pending_transactions' => 0,
                'active_collections' => 0,
            ];
        }

        $balance = (int) $mainWallet->balance;

        if (empty($dateFrom) && empty($dateTo)) {
            $dateFrom = Carbon::now()->startOfMonth()->toDateString();
            $dateTo = Carbon::now()->endOfMonth()->toDateString();
        }

        $baseQuery = $mainWallet->transactions()
            ->where('status', ClubWalletTransactionStatus::Confirmed);

        if (!empty($dateFrom)) {
            $baseQuery->whereDate('confirmed_at', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $baseQuery->whereDate('confirmed_at', '<=', $dateTo);
        }

        $totalIncome = (clone $baseQuery)->where('direction', ClubWalletTransactionDirection::In)->sum('amount');
        $totalExpense = (clone $baseQuery)->where('direction', ClubWalletTransactionDirection::Out)->sum('amount');
        $pendingTransactions = $mainWallet->transactions()->where('status', ClubWalletTransactionStatus::Pending)->count();
        $activeCollections = $club->fundCollections()->where('status', ClubFundCollectionStatus::Active)->count();

        return [
            'balance' => $balance,
            'total_income' => (int) $totalIncome,
            'total_expense' => (int) $totalExpense,
            'pending_transactions' => (int) $pendingTransactions,
            'active_collections' => (int) $activeCollections,
        ];
    }
}
