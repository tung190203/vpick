<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubActivityFeeSplitType;
use App\Enums\ClubActivityParticipantStatus;
use App\Enums\ClubActivityStatus;
use App\Enums\ClubMemberRole;
use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionSourceType;
use App\Enums\ClubWalletTransactionStatus;
use App\Enums\PaymentMethod;
use App\Helpers\ResponseHelper;
use App\Http\Resources\Club\ClubActivityResource;
use App\Models\Club\Club;
use App\Models\Club\ClubActivity;
use App\Models\Club\ClubActivityParticipant;
use App\Models\Club\ClubWalletTransaction;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Rules\ValidRecurringSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClubActivityController extends Controller
{
    private const ACTIVITY_COLLECTED_SUM = 'activityFeeTransactions as collected_amount';

    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'type' => 'sometimes|in:meeting,practice,match,tournament,event,other',
            'statuses' => 'sometimes|array',
            'statuses.*' => 'sometimes|in:all,registered,available,scheduled,ongoing,completed,cancelled',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $query = $club->activities()
            ->with([
                'creator' => User::FULL_RELATIONS,
                'participants.user' => User::FULL_RELATIONS
            ])
            ->withSum(self::ACTIVITY_COLLECTED_SUM, 'amount');

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        // Handle statuses filter
        $statuses = $validated['statuses'] ?? [];
        $hasAll = in_array('all', $statuses);
        $hasRegistered = in_array('registered', $statuses);
        $hasAvailable = in_array('available', $statuses);
        $activityStatuses = array_intersect($statuses, ['scheduled', 'ongoing', 'completed', 'cancelled']);

        // If 'all' is present, ignore all filters
        if (!$hasAll && !empty($statuses)) {
            // Filter by activity status if any
            if (!empty($activityStatuses)) {
                $query->whereIn('status', $activityStatuses);
            }

            // Filter by participation status
            if ($userId && ($hasRegistered || $hasAvailable)) {
                if ($hasRegistered && !$hasAvailable) {
                    // Only registered activities
                    $query->whereHas('participants', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
                } elseif ($hasAvailable && !$hasRegistered) {
                    // Only available (not registered) activities
                    $query->whereDoesntHave('participants', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
                }
                // If both registered and available, don't filter (show all), but will sort later
            }
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('start_time', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('start_time', '<=', $validated['date_to']);
        }

        $perPage = $validated['per_page'] ?? 15;

        // If both registered and available, sort registered first
        if ($userId && $hasRegistered && $hasAvailable && !$hasAll) {
            $query->selectRaw('club_activities.*, EXISTS(SELECT 1 FROM club_activity_participants WHERE club_activity_participants.club_activity_id = club_activities.id AND club_activity_participants.user_id = ?) as is_registered', [$userId])
                ->orderBy('is_registered', 'desc')
                ->orderBy('start_time', 'desc');
        } else {
            $query->orderBy('start_time', 'desc');
        }

        $activities = $query->paginate($perPage);

        $data = ['activities' => ClubActivityResource::collection($activities)];
        $meta = [
            'current_page' => $activities->currentPage(),
            'per_page' => $activities->perPage(),
            'total' => $activities->total(),
            'last_page' => $activities->lastPage(),
        ];
        return ResponseHelper::success($data, 'Lấy danh sách hoạt động thành công', 200, $meta);
    }

    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        $member = $club->activeMembers()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền tạo hoạt động', 403);
        }

        // Convert string "true"/"false" to boolean
        if ($request->has('is_public')) {
            $request->merge(['is_public' => filter_var($request->is_public, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true]);
        }
        if ($request->has('allow_member_invite')) {
            $request->merge(['allow_member_invite' => filter_var($request->allow_member_invite, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false]);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:meeting,practice,match,tournament,event,other',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'cancellation_deadline' => 'nullable|date|before:start_time',
            'mini_tournament_id' => 'nullable|exists:mini_tournaments,id',
            'recurring_schedule' => ['nullable', 'array', new ValidRecurringSchedule()],
            'reminder_minutes' => 'sometimes|integer|min:0',
            'fee_amount' => 'nullable|numeric|min:0',
            'guest_fee' => 'nullable|numeric|min:0',
            'penalty_percentage' => 'nullable|numeric|min:0|max:100',
            'fee_split_type' => ['sometimes', Rule::enum(ClubActivityFeeSplitType::class)],
            'allow_member_invite' => 'sometimes|boolean',
            'is_public' => 'sometimes|boolean',
            'max_participants' => 'nullable|integer|min:1',
            'qr_code_url' => 'nullable|url|max:500',
        ]);

        $activity = ClubActivity::create([
            'club_id' => $club->id,
            'mini_tournament_id' => $validated['mini_tournament_id'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'recurring_schedule' => $validated['recurring_schedule'] ?? null,
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'] ?? null,
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'cancellation_deadline' => $validated['cancellation_deadline'] ?? null,
            'reminder_minutes' => $validated['reminder_minutes'] ?? 15,
            'fee_amount' => $validated['fee_amount'] ?? 0,
            'guest_fee' => $validated['guest_fee'] ?? 0,
            'penalty_percentage' => $validated['penalty_percentage'] ?? 50,
            'fee_split_type' => $validated['fee_split_type'] ?? ClubActivityFeeSplitType::Fixed,
            'allow_member_invite' => isset($validated['allow_member_invite']) ? (bool) $validated['allow_member_invite'] : false,
            'is_public' => isset($validated['is_public']) ? (bool) $validated['is_public'] : true,
            'status' => ClubActivityStatus::Scheduled,
            'created_by' => $userId,
        ]);

        $checkInToken = Str::random(48);
        $activity->update([
            'check_in_token' => $checkInToken,
            'qr_code_url' => $validated['qr_code_url'] ?? $this->buildCheckInUrl($club->id, $activity->id, $checkInToken),
        ]);

        // Tự động thêm người tạo vào danh sách participants với status Accepted
        ClubActivityParticipant::firstOrCreate(
            [
                'club_activity_id' => $activity->id,
                'user_id' => $userId,
            ],
            [
                'status' => ClubActivityParticipantStatus::Accepted,
            ]
        );

        $activity->load([
            'creator' => User::FULL_RELATIONS,
            'participants.user' => User::FULL_RELATIONS
        ]);
        $activity->loadSum(self::ACTIVITY_COLLECTED_SUM, 'amount');
        return ResponseHelper::success(new ClubActivityResource($activity), 'Tạo hoạt động thành công', 201);
    }

    public function show($clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)
            ->with([
                'creator' => User::FULL_RELATIONS,
                'club',
                'participants.user' => User::FULL_RELATIONS,
                'miniTournament'
            ])
            ->withSum(self::ACTIVITY_COLLECTED_SUM, 'amount')
            ->findOrFail($activityId);

        return ResponseHelper::success(new ClubActivityResource($activity), 'Lấy thông tin hoạt động thành công');
    }

    public function update(Request $request, $clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        $club = $activity->club;
        $member = $club->members()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, ['admin', 'manager', 'secretary']) || $activity->created_by !== $userId) {
            return ResponseHelper::error('Không có quyền cập nhật hoạt động này', 403);
        }

        // Convert string "true"/"false" to boolean
        if ($request->has('is_public')) {
            $isPublic = $request->is_public;
            if (is_string($isPublic)) {
                $isPublic = filter_var($isPublic, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
            $request->merge(['is_public' => $isPublic !== null ? (bool) $isPublic : true]);
        }
        if ($request->has('allow_member_invite')) {
            $allowInvite = $request->allow_member_invite;
            if (is_string($allowInvite)) {
                $allowInvite = filter_var($allowInvite, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
            $request->merge(['allow_member_invite' => $allowInvite !== null ? (bool) $allowInvite : false]);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:meeting,practice,match,tournament,event,other',
            'start_time' => 'sometimes|date',
            'end_time' => 'nullable|date|after:start_time',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'cancellation_deadline' => 'nullable|date|before:start_time',
            'recurring_schedule' => ['nullable', 'array', new ValidRecurringSchedule()],
            'reminder_minutes' => 'sometimes|integer|min:0',
            'fee_amount' => 'nullable|numeric|min:0',
            'guest_fee' => 'nullable|numeric|min:0',
            'penalty_percentage' => 'nullable|numeric|min:0|max:100',
            'fee_split_type' => ['sometimes', Rule::enum(ClubActivityFeeSplitType::class)],
            'allow_member_invite' => 'sometimes|boolean',
            'is_public' => 'sometimes|boolean',
            'max_participants' => 'nullable|integer|min:1',
            'qr_code_url' => 'nullable|url|max:500',
        ]);

        $activity->update($validated);
        $activity->load([
            'creator' => User::FULL_RELATIONS,
            'participants.user' => User::FULL_RELATIONS
        ]);
        $activity->loadSum(self::ACTIVITY_COLLECTED_SUM, 'amount');
        return ResponseHelper::success(new ClubActivityResource($activity), 'Cập nhật hoạt động thành công');
    }

    public function destroy($clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary]) || $activity->created_by !== $userId) {
            return ResponseHelper::error('Không có quyền xóa hoạt động này', 403);
        }

        if (!$activity->canBeCancelled()) {
            return ResponseHelper::error('Chỉ có thể xóa hoạt động đang scheduled', 422);
        }

        $activity->delete();

        return ResponseHelper::success('Xóa hoạt động thành công');
    }

    public function complete($clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền đánh dấu hoàn thành', 403);
        }

        $activity->markAsCompleted();
        $activity->load([
            'creator' => User::FULL_RELATIONS,
            'participants.user' => User::FULL_RELATIONS
        ]);
        $activity->loadSum(self::ACTIVITY_COLLECTED_SUM, 'amount');

        return ResponseHelper::success(new ClubActivityResource($activity), 'Hoạt động đã được đánh dấu hoàn thành');
    }

    public function cancel(Request $request, $clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền hủy sự kiện', 403);
        }

        if (!$activity->canBeCancelled()) {
            return ResponseHelper::error('Chỉ có thể hủy sự kiện đang scheduled', 422);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:500',
            'cancel_transactions' => 'required|boolean',
        ]);

        return DB::transaction(function () use ($activity, $club, $userId, $validated) {
            $activity->update([
                'status' => ClubActivityStatus::Cancelled,
                'cancellation_reason' => $validated['cancellation_reason'],
                'cancelled_by' => $userId,
            ]);

            if ($validated['cancel_transactions']) {
                $mainWallet = $club->mainWallet;
                if (!$mainWallet) {
                    return ResponseHelper::error('CLB chưa có ví chính', 404);
                }

                $participants = $activity->acceptedParticipants()
                    ->with(['user', 'walletTransaction'])
                    ->get();

                foreach ($participants as $participant) {
                    $transaction = $participant->walletTransaction;

                    if ($transaction) {
                        if ($transaction->isConfirmed()) {
                            ClubWalletTransaction::create([
                                'club_wallet_id' => $mainWallet->id,
                                'direction' => ClubWalletTransactionDirection::Out,
                                'amount' => $transaction->amount,
                                'source_type' => ClubWalletTransactionSourceType::Activity,
                                'source_id' => $activity->id,
                                'payment_method' => PaymentMethod::BankTransfer,
                                'status' => ClubWalletTransactionStatus::Pending,
                                'description' => "Hoàn tiền cho {$participant->user->full_name} do hủy sự kiện: {$activity->title}",
                                'created_by' => $userId,
                            ]);
                        }

                        $transaction->reject($userId);
                    }
                }
            }

            $activity->load([
                'creator' => User::FULL_RELATIONS,
                'participants.user' => User::FULL_RELATIONS
            ]);
            $activity->loadSum(self::ACTIVITY_COLLECTED_SUM, 'amount');
            return ResponseHelper::success(new ClubActivityResource($activity), 'Sự kiện đã được hủy');
        });
    }

    private function buildCheckInUrl($clubId, $activityId, $token): string
    {
        return url("/api/clubs/{$clubId}/activities/{$activityId}/check-in?token={$token}");
    }
}
