<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionSourceType;
use App\Enums\ClubWalletTransactionStatus;
use App\Enums\PaymentMethod;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Club\ClubWalletTransactionResource;
use App\Models\Club\Club;
use App\Models\Club\ClubWallet;
use App\Models\Club\ClubWalletTransaction;
use App\Services\Club\ClubWalletTransactionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClubWalletTransactionController extends Controller
{
    public function __construct(
        protected ClubWalletTransactionService $transactionService
    ) {
    }

    public function index(Request $request, $clubId)
    {
        $this->normalizeFilterArrayParams($request);
        $club = Club::findOrFail($clubId);
        $validated = $request->validate(array_merge($this->transactionFilterRules(), [
            'wallet_id' => 'sometimes|exists:club_wallets,id',
        ]));

        $transactions = $this->transactionService->getTransactions($club, $validated);

        $data = ['transactions' => ClubWalletTransactionResource::collection($transactions)];
        $meta = [
            'current_page' => $transactions->currentPage(),
            'per_page' => $transactions->perPage(),
            'total' => $transactions->total(),
            'last_page' => $transactions->lastPage(),
        ];
        return ResponseHelper::success($data, 'Lấy danh sách giao dịch thành công', 200, $meta);
    }

    public function myTransactions(Request $request, $clubId)
    {
        $userId = auth()->id();
        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        $this->normalizeFilterArrayParams($request);
        $club = Club::findOrFail($clubId);
        $validated = $request->validate($this->transactionFilterRules());

        $transactions = $this->transactionService->getMyTransactions($club, $userId, $validated);

        $data = ['transactions' => ClubWalletTransactionResource::collection($transactions)];
        $meta = [
            'current_page' => $transactions->currentPage(),
            'per_page' => $transactions->perPage(),
            'total' => $transactions->total(),
            'last_page' => $transactions->lastPage(),
        ];

        return ResponseHelper::success($data, 'Lấy lịch sử giao dịch của tôi thành công', 200, $meta);
    }

    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/secretary/treasurer mới có quyền tạo giao dịch', 403);
        }

        $validated = $request->validate([
            'club_wallet_id' => 'required|exists:club_wallets,id',
            'direction' => ['required', Rule::enum(ClubWalletTransactionDirection::class)],
            'amount' => 'required|numeric|min:0.01',
            'source_type' => ['nullable', Rule::enum(ClubWalletTransactionSourceType::class)],
            'source_id' => 'nullable|integer',
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $wallet = ClubWallet::findOrFail($validated['club_wallet_id']);
        if ($wallet->club_id != $clubId) {
            return ResponseHelper::error('Ví không thuộc CLB này', 403);
        }

        $transaction = $this->transactionService->createTransaction($wallet, $validated, $userId);
        $transaction->load(['wallet', 'creator', 'confirmer']);
        return ResponseHelper::success(new ClubWalletTransactionResource($transaction), 'Tạo giao dịch thành công', 201);
    }

    public function show($clubId, $transactionId)
    {
        $transaction = ClubWalletTransaction::whereHas('wallet', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->with(['wallet', 'creator', 'confirmer'])->findOrFail($transactionId);

        return ResponseHelper::success(new ClubWalletTransactionResource($transaction), 'Lấy thông tin giao dịch thành công');
    }

    public function update(Request $request, $clubId, $transactionId)
    {
        $transaction = ClubWalletTransaction::whereHas('wallet', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->findOrFail($transactionId);

        $userId = auth()->id();
        $club = $transaction->wallet->club;
        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/secretary/treasurer mới có quyền cập nhật', 403);
        }

        $validated = $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',
            'payment_method' => ['sometimes', Rule::enum(PaymentMethod::class)],
            'reference_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            $transaction = $this->transactionService->updateTransaction($transaction, $validated);
            $transaction->load(['wallet', 'creator', 'confirmer']);
            return ResponseHelper::success(new ClubWalletTransactionResource($transaction), 'Cập nhật giao dịch thành công');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function confirm($clubId, $transactionId)
    {
        $transaction = ClubWalletTransaction::whereHas('wallet', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->findOrFail($transactionId);

        $userId = auth()->id();
        $club = $transaction->wallet->club;
        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/secretary/treasurer mới có quyền xác nhận', 403);
        }

        try {
            $transaction = $this->transactionService->confirmTransaction($transaction, $userId);
            $transaction->load(['wallet', 'creator', 'confirmer']);
            return ResponseHelper::success(new ClubWalletTransactionResource($transaction), 'Giao dịch đã được xác nhận');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    public function reject(Request $request, $clubId, $transactionId)
    {
        $transaction = ClubWalletTransaction::whereHas('wallet', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->findOrFail($transactionId);

        $userId = auth()->id();
        $club = $transaction->wallet->club;
        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/secretary/treasurer mới có quyền từ chối', 403);
        }

        try {
            $transaction = $this->transactionService->rejectTransaction($transaction, $userId);
            $transaction->load(['wallet', 'creator', 'confirmer']);
            return ResponseHelper::success(new ClubWalletTransactionResource($transaction), 'Giao dịch đã bị từ chối');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 422);
        }
    }

    private function transactionFilterRules(): array
    {
        return array_merge([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'direction' => ['sometimes', Rule::enum(ClubWalletTransactionDirection::class)],
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ], $this->enumArrayRules('source_types', ClubWalletTransactionSourceType::class), $this->enumArrayRules('statuses', ClubWalletTransactionStatus::class));
    }

    private function enumArrayRules(string $key, string $enumClass): array
    {
        return [
            $key => ['sometimes', 'array'],
            $key . '.*' => [Rule::enum($enumClass)],
        ];
    }

    private function normalizeFilterArrayParams(Request $request): void
    {
        foreach (['statuses', 'source_types'] as $key) {
            if (!$request->has($key)) {
                continue;
            }
            $val = $request->input($key);
            if (is_string($val)) {
                $request->merge([$key => array_values(array_filter(array_map('trim', explode(',', $val))))]);
            } elseif (is_array($val)) {
                $flat = [];
                foreach ($val as $item) {
                    if (is_string($item) && str_contains($item, ',')) {
                        $flat = array_merge($flat, array_map('trim', explode(',', $item)));
                    } else {
                        $flat[] = $item;
                    }
                }
                $request->merge([$key => array_values(array_filter($flat))]);
            }
        }
    }
}
