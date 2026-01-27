<?php

namespace App\Http\Controllers\Club;

use App\Helpers\ResponseHelper;
use App\Models\Club\Club;
use App\Models\Club\ClubWallet;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClubWalletController extends Controller
{
    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        $validated = $request->validate([
            'type' => 'sometimes|in:main,fund,donation',
        ]);

        $query = $club->wallets();

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $wallets = $query->with('transactions')->get();
        $wallets->each(function ($wallet) {
            $wallet->balance = $wallet->balance;
        });

        return ResponseHelper::success($wallets, 'Lấy danh sách ví thành công');
    }

    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền tạo ví', 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:main,fund,donation',
            'currency' => 'sometimes|string|max:3|default:VND',
            'qr_code_url' => 'nullable|string',
        ]);

        $exists = $club->wallets()
            ->where('type', $validated['type'])
            ->where('currency', $validated['currency'] ?? 'VND')
            ->exists();

        if ($exists) {
            return ResponseHelper::error('Ví loại này đã tồn tại', 409);
        }

        $wallet = ClubWallet::create([
            'club_id' => $club->id,
            'type' => $validated['type'],
            'currency' => $validated['currency'] ?? 'VND',
            'qr_code_url' => $validated['qr_code_url'] ?? null,
        ]);

        return ResponseHelper::success($wallet, 'Tạo ví thành công', 201);
    }

    public function show($clubId, $walletId)
    {
        $wallet = ClubWallet::where('club_id', $clubId)
            ->with(['club', 'transactions'])
            ->findOrFail($walletId);

        $wallet->balance = $wallet->balance;
        $wallet->transaction_count = $wallet->transactions()->count();

        return ResponseHelper::success($wallet, 'Lấy thông tin ví thành công');
    }

    public function update(Request $request, $clubId, $walletId)
    {
        $wallet = ClubWallet::where('club_id', $clubId)->findOrFail($walletId);
        $userId = auth()->id();

        $club = $wallet->club;
        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền cập nhật ví', 403);
        }

        $validated = $request->validate([
            'qr_code_url' => 'nullable|string',
        ]);

        $wallet->update($validated);

        return ResponseHelper::success($wallet, 'Cập nhật ví thành công');
    }

    public function destroy($clubId, $walletId)
    {
        $wallet = ClubWallet::where('club_id', $clubId)->findOrFail($walletId);
        $userId = auth()->id();

        $club = $wallet->club;
        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền xóa ví', 403);
        }

        if ($wallet->transactions()->exists()) {
            return ResponseHelper::error('Không thể xóa ví vì có giao dịch', 422);
        }

        $wallet->delete();

        return ResponseHelper::success([], 'Xóa ví thành công');
    }

    public function getBalance($clubId, $walletId)
    {
        $wallet = ClubWallet::where('club_id', $clubId)->findOrFail($walletId);

        return ResponseHelper::success([
            'wallet_id' => $wallet->id,
            'balance' => $wallet->balance,
            'currency' => $wallet->currency,
            'calculated_at' => now(),
        ], 'Lấy số dư ví thành công');
    }

    public function getTransactions(Request $request, $clubId, $walletId)
    {
        $wallet = ClubWallet::where('club_id', $clubId)->findOrFail($walletId);

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'direction' => 'sometimes|in:in,out',
            'status' => 'sometimes|in:pending,confirmed,rejected',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $query = $wallet->transactions()->with(['creator', 'confirmer']);

        if (!empty($validated['direction'])) {
            $query->where('direction', $validated['direction']);
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
}
