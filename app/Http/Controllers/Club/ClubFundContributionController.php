<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubFundCollectionStatus;
use App\Enums\ClubFundContributionStatus;
use App\Helpers\ResponseHelper;
use App\Http\Resources\Club\ClubFundContributionResource;
use App\Models\Club\Club;
use App\Models\Club\ClubFundCollection;
use App\Models\Club\ClubFundContribution;
use App\Http\Controllers\Controller;
use App\Services\ImageOptimizationService;
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

    /**
     * Member nộp tiền bằng ảnh biên lai + ghi chú.
     * POST /clubs/{clubId}/fund-collections/{collectionId}/contributions
     */
    public function store(Request $request, $clubId, $collectionId)
    {
        $collection = ClubFundCollection::where('club_id', $clubId)->findOrFail($collectionId);
        $userId = auth()->id();

        $validated = $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg,gif|max:5120', // 5MB
            'note' => 'nullable|string|max:500',
        ]);

        $errorMessage = null;
        $amountDue = $collection->target_amount;

        if ($collection->status !== ClubFundCollectionStatus::Active) {
            $errorMessage = 'Đợt thu không còn hoạt động';
        } else {
            $existingPending = $collection->contributions()
                ->where('user_id', $userId)
                ->where('status', ClubFundContributionStatus::Pending)
                ->first();

            if ($existingPending) {
                $errorMessage = 'Đóng góp của bạn đang chờ xác nhận';
            } else {
                $assigned = $collection->assignedMembers()->where('user_id', $userId)->first();
                if ($assigned) {
                    $amountDue = $assigned->pivot?->amount_due ?? $amountDue;
                }

                if ($amountDue <= 0) {
                    $errorMessage = 'Số tiền cần đóng không hợp lệ';
                }
            }
        }

        if ($errorMessage) {
            return ResponseHelper::error($errorMessage, 422);
        }

        $imageService = app(ImageOptimizationService::class);
        $file = $request->file('image');
        $receiptUrl = $imageService->optimizeThumbnail($file, 'fund_contribution_receipts', 90);

        $contribution = ClubFundContribution::create([
            'club_fund_collection_id' => $collection->id,
            'user_id' => $userId,
            'amount' => $amountDue,
            'receipt_url' => $receiptUrl,
            'note' => $validated['note'] ?? null,
            'status' => ClubFundContributionStatus::Pending,
        ]);

        $contribution->load(['user', 'walletTransaction']);
        return ResponseHelper::success(new ClubFundContributionResource($contribution), 'Đã gửi biên lai', 201);
    }

    public function show($clubId, $collectionId, $contributionId)
    {
        $contribution = ClubFundContribution::whereHas('fundCollection', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->where('club_fund_collection_id', $collectionId)
            ->with(['user', 'walletTransaction', 'fundCollection'])
            ->findOrFail($contributionId);

        return ResponseHelper::success(new ClubFundContributionResource($contribution), 'Lấy thông tin đóng góp thành công');
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
            return ResponseHelper::success(new ClubFundContributionResource($contribution), 'Đóng góp đã được xác nhận');
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

        $request->validate([
            'reason' => 'required|string',
        ]);

        $contribution->reject();
        $contribution->load(['user', 'walletTransaction']);
        return ResponseHelper::success(new ClubFundContributionResource($contribution), 'Đóng góp đã bị từ chối');
    }
}
