<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Club;
use App\Models\ClubWallet;
use App\Models\ClubWalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClubWalletTransactionController extends Controller
{
    /**
     * Lấy danh sách giao dịch (tất cả ví)
     */
    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'wallet_id' => 'sometimes|exists:club_wallets,id',
            'direction' => 'sometimes|in:in,out',
            'source_type' => 'sometimes|in:monthly_fee,fund_collection,expense,donation,adjustment',
            'status' => 'sometimes|in:pending,confirmed,rejected',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $query = ClubWalletTransaction::whereHas('wallet', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->with(['wallet', 'creator', 'confirmer']);

        if (!empty($validated['wallet_id'])) {
            $query->where('club_wallet_id', $validated['wallet_id']);
        }

        if (!empty($validated['direction'])) {
            $query->where('direction', $validated['direction']);
        }

        if (!empty($validated['source_type'])) {
            $query->where('source_type', $validated['source_type']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return ResponseHelper::success([
            'data' => $transactions->items(),
            'current_page' => $transactions->currentPage(),
            'per_page' => $transactions->perPage(),
            'total' => $transactions->total(),
            'last_page' => $transactions->lastPage(),
        ], 'Lấy danh sách giao dịch thành công');
    }

    /**
     * Tạo giao dịch mới
     */
    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền tạo giao dịch', 403);
        }

        $validated = $request->validate([
            'club_wallet_id' => 'required|exists:club_wallets,id',
            'direction' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0.01',
            'source_type' => 'nullable|in:monthly_fee,fund_collection,expense,donation,adjustment',
            'source_id' => 'nullable|integer',
            'payment_method' => 'required|in:cash,bank_transfer,qr_code,other',
            'reference_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Kiểm tra wallet thuộc club
        $wallet = ClubWallet::findOrFail($validated['club_wallet_id']);
        if ($wallet->club_id != $clubId) {
            return ResponseHelper::error('Ví không thuộc CLB này', 403);
        }

        $transaction = ClubWalletTransaction::create([
            'club_wallet_id' => $validated['club_wallet_id'],
            'direction' => $validated['direction'],
            'amount' => $validated['amount'],
            'source_type' => $validated['source_type'] ?? null,
            'source_id' => $validated['source_id'] ?? null,
            'payment_method' => $validated['payment_method'],
            'status' => 'pending',
            'reference_code' => $validated['reference_code'] ?? null,
            'description' => $validated['description'] ?? null,
            'created_by' => $userId,
        ]);

        $transaction->load(['wallet', 'creator']);

        return ResponseHelper::success($transaction, 'Tạo giao dịch thành công', 201);
    }

    /**
     * Lấy chi tiết giao dịch
     */
    public function show($clubId, $transactionId)
    {
        $transaction = ClubWalletTransaction::whereHas('wallet', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->with(['wallet', 'creator', 'confirmer'])->findOrFail($transactionId);

        return ResponseHelper::success($transaction, 'Lấy thông tin giao dịch thành công');
    }

    /**
     * Cập nhật giao dịch
     */
    public function update(Request $request, $clubId, $transactionId)
    {
        $transaction = ClubWalletTransaction::whereHas('wallet', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->findOrFail($transactionId);

        if ($transaction->status !== 'pending') {
            return ResponseHelper::error('Chỉ có thể cập nhật giao dịch đang pending', 422);
        }

        $userId = auth()->id();
        $club = $transaction->wallet->club;
        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền cập nhật', 403);
        }

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',
            'payment_method' => 'sometimes|in:cash,bank_transfer,qr_code,other',
            'reference_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $transaction->update($validated);
        $transaction->load(['wallet', 'creator']);

        return ResponseHelper::success($transaction, 'Cập nhật giao dịch thành công');
    }

    /**
     * Xác nhận giao dịch
     */
    public function confirm($clubId, $transactionId)
    {
        $transaction = ClubWalletTransaction::whereHas('wallet', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->findOrFail($transactionId);

        if ($transaction->status !== 'pending') {
            return ResponseHelper::error('Chỉ có thể xác nhận giao dịch đang pending', 422);
        }

        $userId = auth()->id();
        $club = $transaction->wallet->club;
        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền xác nhận', 403);
        }

        $transaction->confirm($userId);
        $transaction->load(['wallet', 'creator', 'confirmer']);

        return ResponseHelper::success($transaction, 'Giao dịch đã được xác nhận');
    }

    /**
     * Từ chối giao dịch
     */
    public function reject(Request $request, $clubId, $transactionId)
    {
        $transaction = ClubWalletTransaction::whereHas('wallet', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->findOrFail($transactionId);

        if ($transaction->status !== 'pending') {
            return ResponseHelper::error('Chỉ có thể từ chối giao dịch đang pending', 422);
        }

        $userId = auth()->id();
        $club = $transaction->wallet->club;
        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền từ chối', 403);
        }

        $transaction->reject($userId);
        $transaction->load(['wallet', 'creator', 'confirmer']);

        return ResponseHelper::success($transaction, 'Giao dịch đã bị từ chối');
    }
}
