<?php

namespace App\Services\Club;

use App\Enums\ClubActivityFeeSplitType;
use App\Enums\ClubActivityParticipantStatus;
use App\Enums\ClubActivityStatus;
use App\Enums\ClubMemberRole;
use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionSourceType;
use App\Enums\ClubWalletTransactionStatus;
use App\Enums\PaymentMethod;
use App\Models\Club\Club;
use App\Models\Club\ClubActivity;
use App\Models\Club\ClubActivityParticipant;
use App\Models\Club\ClubWalletTransaction;
use App\Services\ImageOptimizationService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClubActivityService
{
    public function __construct(
        protected ImageOptimizationService $imageService
    ) {
    }

    public function getActivities(Club $club, array $filters, ?int $userId): LengthAwarePaginator
    {
        $query = $club->activities()
            ->with([
                'participants.user',
                'creator'
            ]);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $statuses = $filters['statuses'] ?? [];
        $hasAll = in_array('all', $statuses);
        $hasRegistered = in_array('registered', $statuses);
        $hasAvailable = in_array('available', $statuses);
        $activityStatuses = array_intersect($statuses, ['scheduled', 'ongoing', 'completed', 'cancelled']);

        if (!$hasAll && !empty($statuses)) {
            if (!empty($activityStatuses)) {
                $query->whereIn('status', $activityStatuses);
            }

            if ($userId && ($hasRegistered || $hasAvailable)) {
                if ($hasRegistered && !$hasAvailable) {
                    $query->whereHas('participants', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
                } elseif ($hasAvailable && !$hasRegistered) {
                    $query->whereDoesntHave('participants', function ($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
                }
            }
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('start_time', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('start_time', '<=', $filters['date_to']);
        }

        $perPage = $filters['per_page'] ?? 15;

        if ($userId) {
            $query->selectRaw('club_activities.*, EXISTS(SELECT 1 FROM club_activity_participants WHERE club_activity_participants.club_activity_id = club_activities.id AND club_activity_participants.user_id = ?) as is_registered', [$userId])
                ->orderBy('is_registered', 'desc')
                ->orderBy('start_time', 'asc');
        } else {
            $query->orderBy('start_time', 'asc');
        }

        return $query->paginate($perPage);
    }

    public function createActivity(Club $club, array $data, int $userId): ClubActivity
    {
        $member = $club->activeMembers()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            throw new \Exception('Chỉ admin/manager/secretary mới có quyền tạo hoạt động');
        }

        $endTime = $data['end_time'] ?? null;
        if (!$endTime && isset($data['duration'])) {
            $startTime = Carbon::parse($data['start_time']);
            $endTime = $startTime->copy()->addMinutes($data['duration']);
        }

        $cancellationDeadline = $data['cancellation_deadline'] ?? null;
        if (!$cancellationDeadline && isset($data['cancellation_deadline_hours'])) {
            $startTime = Carbon::parse($data['start_time']);
            $cancellationDeadline = $startTime->copy()->subHours($data['cancellation_deadline_hours']);
        }

        $qrCodeUrl = null;
        if (isset($data['qr_image']) && $data['qr_image'] instanceof UploadedFile) {
            $qrCodeUrl = $this->imageService->optimizeThumbnail($data['qr_image'], 'activity_qr_codes', 90);
        }

        $activity = ClubActivity::create([
            'club_id' => $club->id,
            'mini_tournament_id' => $data['mini_tournament_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'] ?? 'other',
            'recurring_schedule' => $data['recurring_schedule'] ?? null,
            'start_time' => $data['start_time'],
            'end_time' => $endTime,
            'duration' => $data['duration'] ?? null,
            'address' => $data['address'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'cancellation_deadline' => $cancellationDeadline,
            'reminder_minutes' => $data['reminder_minutes'] ?? 15,
            'fee_amount' => $data['fee_amount'] ?? 0,
            'fee_description' => $data['fee_description'] ?? null,
            'guest_fee' => $data['guest_fee'] ?? 0,
            'penalty_amount' => $data['penalty_amount'] ?? 0,
            'fee_split_type' => $data['fee_split_type'] ?? ClubActivityFeeSplitType::Fixed,
            'allow_member_invite' => isset($data['allow_member_invite']) ? (bool) $data['allow_member_invite'] : false,
            'is_public' => isset($data['is_public']) ? (bool) $data['is_public'] : true,
            'max_participants' => $data['max_participants'] ?? null,
            'status' => ClubActivityStatus::Scheduled,
            'created_by' => $userId,
        ]);

        $checkInToken = Str::random(48);
        $activity->update([
            'check_in_token' => $checkInToken,
            'qr_code_url' => $qrCodeUrl,
        ]);

        ClubActivityParticipant::firstOrCreate(
            [
                'club_activity_id' => $activity->id,
                'user_id' => $userId,
            ],
            [
                'status' => ClubActivityParticipantStatus::Accepted,
            ]
        );

        return $activity;
    }

    public function updateActivity(ClubActivity $activity, array $data, int $userId): ClubActivity
    {
        $club = $activity->club;
        $member = $club->members()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary]) || $activity->created_by !== $userId) {
            throw new \Exception('Không có quyền cập nhật hoạt động này');
        }

        if (isset($data['duration']) && isset($data['start_time'])) {
            $startTime = Carbon::parse($data['start_time']);
            $data['end_time'] = $startTime->copy()->addMinutes($data['duration']);
        }

        if (isset($data['cancellation_deadline_hours'])) {
            $startTime = Carbon::parse($data['start_time'] ?? $activity->start_time);
            $data['cancellation_deadline'] = $startTime->copy()->subHours($data['cancellation_deadline_hours']);
        }

        if (isset($data['qr_image']) && $data['qr_image'] instanceof UploadedFile) {
            $data['qr_code_url'] = $this->imageService->optimizeThumbnail($data['qr_image'], 'activity_qr_codes', 90);
        }

        unset($data['cancellation_deadline_hours']);
        unset($data['qr_image']);

        $activity->update($data);

        return $activity;
    }

    public function deleteActivity(ClubActivity $activity, int $userId): void
    {
        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary]) || $activity->created_by !== $userId) {
            throw new \Exception('Không có quyền xóa hoạt động này');
        }

        if (!$activity->canBeCancelled()) {
            throw new \Exception('Chỉ có thể xóa hoạt động đang scheduled');
        }

        $activity->delete();
    }

    public function completeActivity(ClubActivity $activity, int $userId): ClubActivity
    {
        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            throw new \Exception('Chỉ admin/manager/secretary mới có quyền đánh dấu hoàn thành');
        }

        $activity->markAsCompleted();

        return $activity;
    }

    public function cancelActivity(
        ClubActivity $activity,
        int $userId,
        string $cancellationReason,
        bool $cancelTransactions
    ): ClubActivity {
        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            throw new \Exception('Chỉ admin/manager/secretary mới có quyền hủy sự kiện');
        }

        if (!$activity->canBeCancelled()) {
            throw new \Exception('Chỉ có thể hủy sự kiện đang scheduled');
        }

        return DB::transaction(function () use ($activity, $club, $userId, $cancellationReason, $cancelTransactions) {
            $activity->update([
                'status' => ClubActivityStatus::Cancelled,
                'cancellation_reason' => $cancellationReason,
                'cancelled_by' => $userId,
            ]);

            if ($cancelTransactions) {
                $mainWallet = $club->mainWallet;
                if (!$mainWallet) {
                    throw new \Exception('CLB chưa có ví chính');
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

            return $activity;
        });
    }

    public function buildCheckInUrl(int $clubId, int $activityId, string $token): string
    {
        return url("/api/clubs/{$clubId}/activities/{$activityId}/check-in?token={$token}");
    }
}
