<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubFundCollectionStatus;
use App\Enums\ClubFundContributionStatus;
use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionSourceType;
use App\Enums\ClubWalletTransactionStatus;
use App\Enums\PaymentMethod;
use App\Helpers\ResponseHelper;
use App\Http\Resources\Club\ClubFundContributionResource;
use App\Models\Club\Club;
use App\Models\Club\ClubFundCollection;
use App\Models\Club\ClubFundContribution;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClubFundContributionController extends Controller
{
    public function index(Request $request, $clubId, $collectionId)
    {
        $collection = ClubFundCollection::where('club_id', $clubId)->findOrFail($collectionId);

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'user_id' => 'sometimes|exists:users,id',
            'status' => ['sometimes', Rule::enum(ClubFundContributionStatus::class)],
        ]);

        $query = $collection->contributions()->with(['user', 'walletTransaction']);

        if (!empty($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $contributions = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = ['contributions' => ClubFundContributionResource::collection($contributions)];
        $meta = [
            'current_page' => $contributions->currentPage(),
            'per_page' => $contributions->perPage(),
            'total' => $contributions->total(),
            'last_page' => $contributions->lastPage(),
        ];
        return ResponseHelper::success($data, 'Lấy danh sách đóng góp thành công', 200, $meta);
    }

    public function store(Request $request, $clubId, $collectionId)
    {
        $collection = ClubFundCollection::where('club_id', $clubId)->findOrFail($collectionId);
        $userId = auth()->id();

        if ($collection->status !== ClubFundCollectionStatus::Active) {
            return ResponseHelper::error('Đợt thu không còn hoạt động', 422);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'reference_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($collection, $validated, $userId) {
            $contribution = ClubFundContribution::create([
                'club_fund_collection_id' => $collection->id,
                'user_id' => $userId,
                'amount' => $validated['amount'],
                'status' => 'pending',
            ]);

            $club = $collection->club;
            $mainWallet = $club->mainWallet;
            if ($mainWallet) {
                $transaction = $mainWallet->transactions()->create([
                    'direction' => 'in',
                    'amount' => $validated['amount'],
                    'source_type' => 'fund_collection',
                    'source_id' => $contribution->id,
                    'payment_method' => $validated['payment_method'],
                    'status' => 'pending',
                    'reference_code' => $validated['reference_code'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'created_by' => $userId,
                ]);

                $contribution->update(['wallet_transaction_id' => $transaction->id]);
            }

            $contribution->load(['user', 'walletTransaction']);

            return ResponseHelper::success($contribution, 'Đóng góp thành công', 201);
        });
    }

    public function show($clubId, $collectionId, $contributionId)
    {
        $contribution = ClubFundContribution::whereHas('fundCollection', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->where('club_fund_collection_id', $collectionId)
            ->with(['user', 'walletTransaction', 'fundCollection'])
            ->findOrFail($contributionId);

        return ResponseHelper::success($contribution, 'Lấy thông tin đóng góp thành công');
    }

    public function confirm($clubId, $collectionId, $contributionId)
    {
        $contribution = ClubFundContribution::whereHas('fundCollection', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->where('club_fund_collection_id', $collectionId)
            ->findOrFail($contributionId);

        $club = $contribution->fundCollection->club;
        $userId = auth()->id();

        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền xác nhận', 403);
        }

        if ($contribution->status !== ClubFundContributionStatus::Pending) {
            return ResponseHelper::error('Chỉ có thể xác nhận đóng góp đang pending', 422);
        }

        return DB::transaction(function () use ($contribution) {
            $contribution->confirm();
            
            if ($contribution->walletTransaction) {
                $contribution->walletTransaction->confirm(auth()->id());
            }

            $contribution->load(['user', 'walletTransaction']);

            return ResponseHelper::success($contribution, 'Đóng góp đã được xác nhận');
        });
    }

    public function reject(Request $request, $clubId, $collectionId, $contributionId)
    {
        $contribution = ClubFundContribution::whereHas('fundCollection', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->where('club_fund_collection_id', $collectionId)
            ->findOrFail($contributionId);

        $club = $contribution->fundCollection->club;
        $userId = auth()->id();

        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền từ chối', 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $contribution->reject();
        $contribution->load(['user', 'walletTransaction']);

        return ResponseHelper::success($contribution, 'Đóng góp đã bị từ chối');
    }
}
