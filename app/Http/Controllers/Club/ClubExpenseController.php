<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionSourceType;
use App\Enums\ClubWalletTransactionStatus;
use App\Enums\PaymentMethod;
use App\Helpers\ResponseHelper;
use App\Http\Resources\Club\ClubExpenseResource;
use App\Models\Club\Club;
use App\Models\Club\ClubExpense;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClubExpenseController extends Controller
{
    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'spent_by' => 'sometimes|exists:users,id',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $query = $club->expenses()->with(['spender', 'walletTransaction']);

        if (!empty($validated['spent_by'])) {
            $query->where('spent_by', $validated['spent_by']);
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('spent_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('spent_at', '<=', $validated['date_to']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $expenses = $query->orderBy('spent_at', 'desc')->paginate($perPage);

        $data = ['expenses' => ClubExpenseResource::collection($expenses)];
        $meta = [
            'current_page' => $expenses->currentPage(),
            'per_page' => $expenses->perPage(),
            'total' => $expenses->total(),
            'last_page' => $expenses->lastPage(),
        ];
        return ResponseHelper::success($data, 'Lấy danh sách chi phí thành công', 200, $meta);
    }

    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền tạo chi phí', 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,qr_code,other',
            'spent_at' => 'nullable|date',
            'note' => 'nullable|string',
            'reference_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($club, $validated, $userId) {
            $expense = ClubExpense::create([
                'club_id' => $club->id,
                'title' => $validated['title'],
                'amount' => $validated['amount'],
                'spent_by' => $userId,
                'spent_at' => $validated['spent_at'] ?? now(),
                'note' => $validated['note'] ?? null,
            ]);

            $mainWallet = $club->mainWallet;
            if ($mainWallet) {
                $transaction = $mainWallet->transactions()->create([
                    'direction' => ClubWalletTransactionDirection::Out,
                    'amount' => $validated['amount'],
                    'source_type' => ClubWalletTransactionSourceType::Expense,
                    'source_id' => $expense->id,
                    'payment_method' => $validated['payment_method'],
                    'status' => ClubWalletTransactionStatus::Pending,
                    'reference_code' => $validated['reference_code'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'created_by' => $userId,
                ]);

                $expense->update(['wallet_transaction_id' => $transaction->id]);
            }

            $expense->load(['spender', 'walletTransaction']);
            return ResponseHelper::success(new ClubExpenseResource($expense), 'Tạo chi phí thành công', 201);
        });
    }

    public function show($clubId, $expenseId)
    {
        $expense = ClubExpense::where('club_id', $clubId)
            ->with(['spender', 'walletTransaction', 'club'])
            ->findOrFail($expenseId);

        return ResponseHelper::success(new ClubExpenseResource($expense), 'Lấy thông tin chi phí thành công');
    }

    public function update(Request $request, $clubId, $expenseId)
    {
        $expense = ClubExpense::where('club_id', $clubId)->findOrFail($expenseId);
        $userId = auth()->id();

        $club = $expense->club;
        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền cập nhật', 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0.01',
            'spent_at' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $expense->update($validated);
        $expense->load(['spender', 'walletTransaction']);
        return ResponseHelper::success(new ClubExpenseResource($expense), 'Cập nhật chi phí thành công');
    }

    public function destroy($clubId, $expenseId)
    {
        $expense = ClubExpense::where('club_id', $clubId)->findOrFail($expenseId);
        $userId = auth()->id();

        $club = $expense->club;
        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền xóa', 403);
        }

        $expense->delete();

        return ResponseHelper::success([], 'Xóa chi phí thành công');
    }

    public function getStatistics(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        $validated = $request->validate([
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $query = $club->expenses();

        if (!empty($validated['date_from'])) {
            $query->whereDate('spent_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('spent_at', '<=', $validated['date_to']);
        }

        $stats = [
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

        return ResponseHelper::success($stats, 'Lấy thống kê chi phí thành công');
    }
}
