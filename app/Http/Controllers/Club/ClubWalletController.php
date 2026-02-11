<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionStatus;
use App\Enums\ClubWalletType;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Club\ClubWalletResource;
use App\Http\Resources\Club\ClubWalletTransactionResource;
use App\Models\Club\Club;
use App\Models\Club\ClubWallet;
use App\Services\Club\ClubWalletService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClubWalletController extends Controller
{
    public function __construct(
        protected ClubWalletService $walletService
    ) {
    }

    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        $validated = $request->validate([
            'type' => ['sometimes', Rule::enum(ClubWalletType::class)],
        ]);

        $wallets = $this->walletService->getWallets($club, $validated);

        return ResponseHelper::success(ClubWalletResource::collection($wallets), 'Lấy danh sách ví thành công');
    }

    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/secretary/treasurer mới có quyền tạo ví', 403);
        }

        $validated = $request->validate([
            'type' => ['required', Rule::enum(ClubWalletType::class)],
            'currency' => 'sometimes|string|max:3',
            'qr_code_url' => 'nullable|string',
        ]);

        try {
            $wallet = $this->walletService->createWallet($club, $validated);
            return ResponseHelper::success(new ClubWalletResource($wallet), 'Tạo ví thành công', 201);
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 409);
        }
    }

    public function show($clubId, $walletId)
    {
        $wallet = ClubWallet::where('id', $walletId)
            ->where('club_id', $clubId)
            ->with(['club', 'transactions'])
            ->withCount('transactions')
            ->firstOrFail();

        $userId = auth()->id();
        if (!$wallet->club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/secretary/treasurer mới có quyền xem thông tin ví', 403);
        }

        return ResponseHelper::success(new ClubWalletResource($wallet), 'Lấy thông tin ví thành công');
    }

    public function update(Request $request, $clubId, $walletId)
    {
        $wallet = ClubWallet::where('id', $walletId)
            ->where('club_id', $clubId)
            ->firstOrFail();
        $userId = auth()->id();

        $club = $wallet->club;
        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/secretary/treasurer mới có quyền cập nhật ví', 403);
        }

        $validated = $request->validate([
            'qr_code_url' => 'nullable|string',
        ]);

        $wallet = $this->walletService->updateWallet($wallet, $validated);

        return ResponseHelper::success(new ClubWalletResource($wallet->fresh()), 'Cập nhật ví thành công');
    }

    public function destroy($clubId, $walletId)
    {
        $wallet = ClubWallet::where('id', $walletId)
            ->where('club_id', $clubId)
            ->with('club')
            ->firstOrFail();

        if (!$wallet->club->canManage(auth()->id())) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền xóa ví', 403);
        }

        try {
            $this->walletService->deleteWallet($wallet);
            return ResponseHelper::success('Xóa ví thành công');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
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
            'direction' => ['sometimes', Rule::enum(ClubWalletTransactionDirection::class)],
            'status' => ['sometimes', Rule::enum(ClubWalletTransactionStatus::class)],
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $transactions = $this->walletService->getTransactions($wallet, $validated);

        $data = ['transactions' => ClubWalletTransactionResource::collection($transactions)];
        $meta = [
            'current_page' => $transactions->currentPage(),
            'per_page' => $transactions->perPage(),
            'total' => $transactions->total(),
            'last_page' => $transactions->lastPage(),
        ];
        return ResponseHelper::success($data, 'Lấy danh sách giao dịch thành công', 200, $meta);
    }

    public function getFundOverview(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        $validated = $request->validate([
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $overview = $this->walletService->getFundOverview($club, $validated['date_from'] ?? null, $validated['date_to'] ?? null);

        return ResponseHelper::success($overview, 'Lấy tổng quan quỹ thành công');
    }

    public function getFundQrCode($clubId)
    {
        $club = Club::findOrFail($clubId);
        $mainWallet = $club->mainWallet;

        if (!$mainWallet) {
            return ResponseHelper::error('CLB chưa có ví chính', 404);
        }

        if (!$mainWallet->qr_code_url) {
            return ResponseHelper::error('Ví chưa có mã QR thanh toán', 404);
        }

        return ResponseHelper::success([
            'club_id' => $club->id,
            'wallet_id' => $mainWallet->id,
            'qr_code_url' => $mainWallet->qr_code_url,
        ], 'Lấy mã QR thanh toán quỹ CLB thành công');
    }
}
