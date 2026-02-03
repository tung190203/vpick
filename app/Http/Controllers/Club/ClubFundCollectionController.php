<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubFundCollectionStatus;
use App\Enums\ClubFundContributionStatus;
use App\Enums\ClubMemberRole;
use App\Helpers\ResponseHelper;
use App\Http\Resources\Club\ClubFundContributionResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\Club\ClubFundCollectionResource;
use App\Models\Club\Club;
use App\Models\Club\ClubFundCollection;
use App\Models\Club\ClubMember;
use App\Http\Controllers\Controller;
use App\Services\ImageOptimizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClubFundCollectionController extends Controller
{
    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        // Mặc định chỉ lấy các đợt thu đang active và chưa quá hạn
        $query = $club->fundCollections()
            ->activeAndNotExpired()
            ->with([
                'creator',
                'contributions' => function ($q) {
                    $q->where('status', ClubFundContributionStatus::Pending)
                        ->with('user');
                },
            ])
            ->withCount([
                'contributions',
                'contributions as confirmed_count' => function ($q) {
                    $q->where('status', ClubFundContributionStatus::Confirmed);
                },
                'contributions as pending_count' => function ($q) {
                    $q->where('status', ClubFundContributionStatus::Pending);
                },
            ]);

        $perPage = $validated['per_page'] ?? 15;
        $collections = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Auto-update status cho các đợt thu đã quá hạn
        $expiredCollections = $club->fundCollections()
            ->where('status', ClubFundCollectionStatus::Active)
            ->whereNotNull('end_date')
            ->where('end_date', '<', now()->startOfDay())
            ->get();

        foreach ($expiredCollections as $expired) {
            $expired->update(['status' => ClubFundCollectionStatus::Completed]);
        }

        $data = [
            'collections' => ClubFundCollectionResource::collection($collections),
        ];
        $meta = [
            'current_page' => $collections->currentPage(),
            'per_page' => $collections->perPage(),
            'total' => $collections->total(),
            'last_page' => $collections->lastPage(),
        ];

        return ResponseHelper::success($data, 'Lấy danh sách đợt thu thành công', 200, $meta);
    }

    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền tạo đợt thu', 403);
        }

        $validated = $request->validate([
            'title' => 'required_without:description|nullable|string|max:255',
            'description' => 'required_without:title|nullable|string',
            'target_amount' => 'required|numeric|min:0.01',
            'amount_per_member' => 'required|numeric|min:0.01',
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'exists:users,id',
            'currency' => 'sometimes|string|max:3',
            'start_date' => 'required|date',
            'deadline' => 'nullable|date|after:start_date',
            'end_date' => 'nullable|date|after:start_date',
            'qr_code_url' => 'nullable|string',
        ]);

        // Map deadline -> end_date nếu có
        $endDate = $validated['end_date'] ?? $validated['deadline'] ?? null;
        $titleOrDescription = $validated['title'] ?? $validated['description'] ?? '';

        $collection = ClubFundCollection::create([
            'club_id' => $club->id,
            'title' => $titleOrDescription,
            'description' => $titleOrDescription,
            'target_amount' => $validated['target_amount'],
            'collected_amount' => 0,
            'currency' => $validated['currency'] ?? 'VND',
            'start_date' => $validated['start_date'],
            'end_date' => $endDate,
            'status' => ClubFundCollectionStatus::Active,
            'qr_code_url' => $validated['qr_code_url'] ?? null,
            'created_by' => $userId,
        ]);

        // Sync assignedMembers với amount_due
        $amountPerMember = (float) $validated['amount_per_member'];
        $syncData = [];
        foreach ($validated['member_ids'] as $memberId) {
            $syncData[$memberId] = ['amount_due' => $amountPerMember];
        }
        $collection->assignedMembers()->sync($syncData);

        $collection->load(['creator', 'club', 'contributions.user', 'assignedMembers']);

        return ResponseHelper::success(new ClubFundCollectionResource($collection), 'Tạo đợt thu thành công', 201);
    }

    public function show($clubId, $collectionId)
    {
        $collection = ClubFundCollection::where('club_id', $clubId)
            ->with(['creator', 'club'])
            ->findOrFail($collectionId);

        $confirmedContributions = $collection->contributions()
            ->where('status', ClubFundContributionStatus::Confirmed)
            ->with('user')
            ->get();
        $pendingContributions = $collection->contributions()
            ->where('status', ClubFundContributionStatus::Pending)
            ->with('user')
            ->get();

        $confirmedByUser = $confirmedContributions->keyBy('user_id');
        $pendingByUser = $pendingContributions->keyBy('user_id');
        $amountPerMember = (float) ($collection->amount_per_member ?? $collection->target_amount);

        $assignedMembers = $collection->assignedMembers()->withPivot('amount_due')->get();
        if ($assignedMembers->isNotEmpty()) {
            $memberSources = $assignedMembers->map(function ($user) use ($amountPerMember) {
                return [
                    'user' => $user,
                    'amount_due' => (float) ($user->pivot?->amount_due ?? $amountPerMember),
                ];
            });
        } else {
            $clubMembers = $collection->club->activeMembers()->with('user')->get();
            $memberSources = $clubMembers->map(function ($member) use ($amountPerMember) {
                return [
                    'user' => $member->user,
                    'amount_due' => $amountPerMember,
                ];
            })->filter(fn ($item) => $item['user'] !== null)->values();
        }

        $paidMembers = $memberSources->filter(function ($item) use ($confirmedByUser) {
            return $confirmedByUser->has($item['user']->id);
        })->map(function ($item) use ($confirmedByUser) {
            $contribution = $confirmedByUser->get($item['user']->id);
            return [
                'user' => new UserResource($item['user']),
                'amount_due' => (float) $item['amount_due'],
                'amount_paid' => (float) $contribution->amount,
                'payment_status' => ClubFundContributionStatus::Confirmed->value,
                'paid_at' => $contribution->created_at?->toISOString(),
                'contribution' => new ClubFundContributionResource($contribution),
            ];
        })->values();

        $unpaidMembers = $memberSources->filter(function ($item) use ($confirmedByUser) {
            return !$confirmedByUser->has($item['user']->id);
        })->map(function ($item) use ($pendingByUser) {
            $pendingContribution = $pendingByUser->get($item['user']->id);
            return [
                'user' => new UserResource($item['user']),
                'amount_due' => (float) $item['amount_due'],
                'amount_paid' => (float) ($pendingContribution?->amount ?? 0),
                'payment_status' => $pendingContribution
                    ? ClubFundContributionStatus::Pending->value
                    : 'unpaid',
                'paid_at' => $pendingContribution?->created_at?->toISOString(),
                'contribution' => $pendingContribution
                    ? new ClubFundContributionResource($pendingContribution)
                    : null,
            ];
        })->values();

        return ResponseHelper::success([
            'collection' => new ClubFundCollectionResource($collection),
            'paid_members' => $paidMembers,
            'unpaid_members' => $unpaidMembers,
            'summary' => [
                'paid_count' => $paidMembers->count(),
                'pending_count' => $pendingContributions->count(),
                'unpaid_count' => $unpaidMembers->where('payment_status', 'unpaid')->count(),
            ],
        ], 'Lấy thông tin đợt thu thành công');
    }

    public function update(Request $request, $clubId, $collectionId)
    {
        $collection = ClubFundCollection::where('club_id', $clubId)->findOrFail($collectionId);
        $userId = auth()->id();

        $club = $collection->club;
        if (!$club->canManageFinance($userId) || $collection->created_by !== $userId) {
            return ResponseHelper::error('Không có quyền cập nhật đợt thu này', 403);
        }

        if ($collection->status !== ClubFundCollectionStatus::Active) {
            return ResponseHelper::error('Chỉ có thể cập nhật đợt thu đang active', 422);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'target_amount' => 'sometimes|numeric|min:0.01',
            'end_date' => 'nullable|date|after:start_date',
            'qr_code_url' => 'nullable|string',
        ]);

        $collection->update($validated);
        $collection->load(['creator', 'club', 'contributions.user']);

        return ResponseHelper::success(new ClubFundCollectionResource($collection), 'Cập nhật đợt thu thành công');
    }

    public function destroy($clubId, $collectionId)
    {
        $collection = ClubFundCollection::where('club_id', $clubId)->findOrFail($collectionId);
        $userId = auth()->id();

        $club = $collection->club;
        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền hủy đợt thu', 403);
        }

        if ($collection->status !== ClubFundCollectionStatus::Active) {
            return ResponseHelper::error('Chỉ có thể hủy đợt thu đang active', 422);
        }

        $collection->update(['status' => ClubFundCollectionStatus::Cancelled]);

        return ResponseHelper::success('Đợt thu đã được hủy');
    }

    public function getQrCode($clubId, $collectionId)
    {
        $collection = ClubFundCollection::where('club_id', $clubId)->findOrFail($collectionId);

        if (!$collection->qr_code_url) {
            return ResponseHelper::error('Đợt thu chưa có mã QR', 404);
        }

        return ResponseHelper::success([
            'qr_code_url' => $collection->qr_code_url,
            'qr_code_data' => null,
        ], 'Lấy mã QR thành công');
    }

    /**
     * Danh sách mã QR (các đợt thu có mã QR) – cho màn "Mã QR" / "MÃ QR HIỆN CÓ".
     * GET /clubs/{clubId}/fund-collections/qr-codes
     */
    public function listQrCodes(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $perPage = $validated['per_page'] ?? 15;
        $query = $club->fundCollections()
            ->whereNotNull('qr_code_url')
            ->where('qr_code_url', '!=', '')
            ->with(['creator'])
            ->orderBy('created_at', 'desc');

        $collections = $query->paginate($perPage);

        $data = [
            'qr_codes' => ClubFundCollectionResource::collection($collections),
        ];
        $meta = [
            'current_page' => $collections->currentPage(),
            'per_page' => $collections->perPage(),
            'total' => $collections->total(),
            'last_page' => $collections->lastPage(),
        ];

        return ResponseHelper::success($data, 'Lấy danh sách mã QR thành công', 200, $meta);
    }

    /**
     * Tạo mã QR mới – upload ảnh QR + số tiền + nội dung (theo Figma "THÊM MÃ QR MỚI").
     * POST /clubs/{clubId}/fund-collections/qr-codes
     */
    public function createQrCode(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền tạo mã QR', 403);
        }

        $validated = $request->validate([
            'image' => 'required|image|mimes:png,jpg,jpeg,gif|max:5120', // 5MB
            'amount' => 'required|numeric|min:0.01',
            'content' => 'required|string|max:300',
            'apply_to_other_clubs' => 'sometimes|boolean',
        ]);

        $imageService = app(ImageOptimizationService::class);
        $file = $request->file('image');
        $qrCodeUrl = $imageService->optimizeThumbnail($file, 'qr_codes', 90);

        $title = Str::limit($validated['content'], 255);
        $today = now()->format('Y-m-d');

        $collection = DB::transaction(function () use (
            $club,
            $userId,
            $validated,
            $title,
            $today,
            $qrCodeUrl
        ) {
            $primary = ClubFundCollection::create([
                'club_id' => $club->id,
                'title' => $title,
                'description' => $validated['content'],
                'target_amount' => $validated['amount'],
                'collected_amount' => 0,
                'currency' => 'VND',
                'start_date' => $today,
                'end_date' => null,
                'status' => ClubFundCollectionStatus::Pending,
                'qr_code_url' => $qrCodeUrl,
                'created_by' => $userId,
            ]);

            if (!empty($validated['apply_to_other_clubs'])) {
                $clubIds = ClubMember::query()
                    ->active()
                    ->where('user_id', $userId)
                    ->whereIn('role', [
                        ClubMemberRole::Admin,
                        ClubMemberRole::Manager,
                        ClubMemberRole::Treasurer,
                    ])
                    ->where('club_id', '!=', $club->id)
                    ->pluck('club_id')
                    ->unique()
                    ->values();

                foreach ($clubIds as $otherClubId) {
                    ClubFundCollection::create([
                        'club_id' => $otherClubId,
                        'title' => $title,
                        'description' => $validated['content'],
                        'target_amount' => $validated['amount'],
                        'collected_amount' => 0,
                        'currency' => 'VND',
                        'start_date' => $today,
                        'end_date' => null,
                        'status' => ClubFundCollectionStatus::Pending,
                        'qr_code_url' => $qrCodeUrl,
                        'created_by' => $userId,
                    ]);
                }
            }

            return $primary;
        });

        $collection->load(['creator', 'club', 'contributions.user']);

        return ResponseHelper::success(new ClubFundCollectionResource($collection), 'Tạo mã QR thành công', 201);
    }

    /**
     * Lấy các đợt thu liên quan đến member (my contributions)
     * GET /clubs/{clubId}/fund-collections/my-collections
     */
    public function getMyCollections(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        // Lấy tất cả các đợt thu active (tạm thời lấy tất cả vì chưa có assignedMembers)
        $assignedCollections = $club->fundCollections()
            ->activeAndNotExpired()
            // ->whereHas('assignedMembers', function ($q) use ($userId) {
            //     $q->where('user_id', $userId);
            // })
            ->with(['creator'])
            ->get();

        // Lấy các contribution của user
        $contributions = \App\Models\Club\ClubFundContribution::whereIn('club_fund_collection_id', $assignedCollections->pluck('id'))
            ->where('user_id', $userId)
            ->get()
            ->keyBy('club_fund_collection_id');

        $result = $assignedCollections->map(function ($collection) use ($contributions) {
            $contribution = $contributions->get($collection->id);
            $amountDue = $collection->target_amount; // Tạm thời dùng target_amount vì chưa có assignedMembers

            return [
                'id' => $collection->id,
                'title' => $collection->title,
                'description' => $collection->description,
                'amount_due' => (float) $amountDue,
                'currency' => $collection->currency,
                'end_date' => $collection->end_date?->format('Y-m-d'),
                'status' => $collection->status->value,
                'qr_code_url' => $collection->qr_code_url,

                // Trạng thái đóng góp của user
                'my_contribution' => $contribution ? [
                    'id' => $contribution->id,
                    'amount' => (float) $contribution->amount,
                    'status' => $contribution->status->value,
                    'created_at' => $contribution->created_at->toISOString(),
                ] : null,

                'payment_status' => $contribution ? $contribution->status->value : 'unpaid',
                'is_overdue' => $collection->end_date && now()->isAfter($collection->end_date),
            ];
        });

        // Phân loại
        $needPayment = $result->filter(fn($item) => $item['payment_status'] === 'unpaid')->values();
        $pending = $result->filter(fn($item) => $item['payment_status'] === 'pending')->values();
        $confirmed = $result->filter(fn($item) => $item['payment_status'] === 'confirmed')->values();

        return ResponseHelper::success([
            'need_payment' => $needPayment,
            'pending' => $pending,
            'confirmed' => $confirmed,
            'summary' => [
                'need_payment_count' => $needPayment->count(),
                'pending_count' => $pending->count(),
                'confirmed_count' => $confirmed->count(),
            ],
        ], 'Lấy danh sách đợt thu của tôi thành công');
    }
}
