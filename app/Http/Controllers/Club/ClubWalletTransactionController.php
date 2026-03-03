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
use App\Models\Club\ClubActivity;
use App\Models\Club\ClubActivityParticipant;
use App\Models\Club\ClubWallet;
use App\Models\Club\ClubWalletTransaction;
use App\Services\Club\ClubWalletTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'direction' => ['required_without:participant_ids', Rule::enum(ClubWalletTransactionDirection::class)],
            'amount' => 'required|numeric|min:0.01',
            'source_type' => ['nullable', Rule::enum(ClubWalletTransactionSourceType::class)],
            'source_id' => 'nullable|integer',
            'activity_id' => ['required_with:participant_ids', 'required_if:source_type,activity', 'nullable', 'exists:club_activities,id'],
            'participant_id' => ['nullable', 'exists:club_activity_participants,id'],
            'participant_ids' => ['nullable', 'array', 'min:1'],
            'participant_ids.*' => ['integer', 'exists:club_activity_participants,id'],
            'collection_type' => ['required_with:participant_ids', 'in:fixed,equal'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'included_in_club_fund' => 'sometimes|boolean',
        ]);

        if (!empty($validated['participant_ids']) && !empty($validated['participant_id'])) {
            return ResponseHelper::error('Chỉ dùng participant_id hoặc participant_ids, không dùng cả hai', 422);
        }

        $wallet = ClubWallet::findOrFail($validated['club_wallet_id']);
        if ($wallet->club_id != $clubId) {
            return ResponseHelper::error('Ví không thuộc CLB này', 403);
        }

        $activityId = $validated['activity_id'] ?? null;
        $participantId = $validated['participant_id'] ?? null;
        $participantIds = $validated['participant_ids'] ?? [];

        // Batch mode: tạo nhiều giao dịch thu cho nhiều participant trong 1 request
        if (!empty($participantIds)) {
            $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
            try {
                $transactions = DB::transaction(function () use ($wallet, $activity, $participantIds, $validated) {
                    return $this->transactionService->createBatchTransactions(
                        $wallet,
                        $activity,
                        $participantIds,
                        $validated['collection_type'],
                        (float) $validated['amount'],
                        [
                            'payment_method' => $validated['payment_method'],
                            'reference_code' => $validated['reference_code'] ?? null,
                            'description' => $validated['description'] ?? null,
                            'included_in_club_fund' => $validated['included_in_club_fund'] ?? false,
                        ]
                    );
                });
            } catch (\InvalidArgumentException $e) {
                return ResponseHelper::error($e->getMessage(), 422);
            }
            ClubActivity::where('id', $activityId)->update(['has_collection' => true]);
            $transactions->load(['wallet', 'creator', 'confirmer']);
            return ResponseHelper::success(
                ClubWalletTransactionResource::collection($transactions),
                'Tạo ' . $transactions->count() . ' giao dịch thành công',
                201
            );
        }

        // Single transaction mode
        if ($activityId) {
            ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
            $validated['source_type'] = ClubWalletTransactionSourceType::Activity;
            $validated['source_id'] = $activityId;
        }

        $participant = null;
        if ($participantId) {
            $participant = ClubActivityParticipant::where('id', $participantId)
                ->whereHas('activity', fn ($q) => $q->where('club_id', $clubId))
                ->with('activity')
                ->firstOrFail();
            if ($activityId && (int) $participant->club_activity_id !== (int) $activityId) {
                return ResponseHelper::error('Participant không thuộc activity này', 422);
            }
        }

        $creatorId = $participant ? $participant->user_id : $userId;

        $transaction = $this->transactionService->createTransaction($wallet, $validated, $creatorId);

        if ($participantId) {
            ClubActivityParticipant::where('id', $participantId)->update(['wallet_transaction_id' => $transaction->id]);
        }

        if ($activityId) {
            ClubActivity::where('id', $activityId)->update(['has_collection' => true]);
        }
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
