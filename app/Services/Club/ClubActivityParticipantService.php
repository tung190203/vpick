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
use App\Models\Club\ClubActivity;
use App\Models\Club\ClubActivityParticipant;
use App\Models\Club\ClubWalletTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClubActivityParticipantService
{
    public function getParticipants(ClubActivity $activity, ?string $statusFilter = null): array
    {
        $allParticipants = $activity->participants()->get();

        $query = $activity->participants()->with('user');

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        } else {
            $query->where('status', ClubActivityParticipantStatus::Accepted);
        }

        $participants = $query->get();

        return [
            'participants' => $participants,
            'total' => $participants->count(),
            'pending_count' => $allParticipants->where('status', ClubActivityParticipantStatus::Pending)->count(),
            'invited_count' => $allParticipants->where('status', ClubActivityParticipantStatus::Invited)->count(),
            'accepted_count' => $allParticipants->where('status', ClubActivityParticipantStatus::Accepted)->count(),
        ];
    }

    public function joinActivity(ClubActivity $activity, int $userId): ClubActivityParticipant
    {
        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $userId)->first();

        if (!$member) {
            throw new \Exception('Bạn không phải thành viên CLB');
        }

        // Check if user already has a participant record
        $existingParticipant = $activity->participants()->where('user_id', $userId)->first();

        if ($existingParticipant) {
            if (in_array($existingParticipant->status, [
                ClubActivityParticipantStatus::Pending,
                ClubActivityParticipantStatus::Invited,
                ClubActivityParticipantStatus::Accepted,
                ClubActivityParticipantStatus::Attended,
            ])) {
                throw new \Exception('Bạn đã tham gia hoạt động này');
            }

            // If status is Declined or Absent, allow rejoin by updating status to Pending
            $existingParticipant->update([
                'status' => ClubActivityParticipantStatus::Pending,
                'wallet_transaction_id' => null, // Reset transaction
            ]);

            return $existingParticipant;
        }

        if ($activity->max_participants !== null && $activity->acceptedParticipants()->count() >= $activity->max_participants) {
            throw new \Exception('Sự kiện đã đủ số lượng người tham gia');
        }

        return ClubActivityParticipant::create([
            'club_activity_id' => $activity->id,
            'user_id' => $userId,
            'status' => ClubActivityParticipantStatus::Pending,
        ]);
    }

    public function inviteUsers(ClubActivity $activity, array $userIds, int $inviterId): array
    {
        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $inviterId)->first();

        if (!$member) {
            throw new \Exception('Bạn không phải thành viên CLB');
        }

        $canInvite = in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])
            || $activity->allow_member_invite;

        if (!$canInvite) {
            throw new \Exception('Chỉ admin/manager/secretary hoặc khi sự kiện cho phép thành viên mời mới có quyền mời');
        }

        $currentCount = $activity->participants()->count();
        $maxParticipants = $activity->max_participants;

        $invited = [];
        foreach ($userIds as $userId) {
            if ($maxParticipants !== null && $currentCount >= $maxParticipants) {
                break;
            }

            if (!$activity->participants()->where('user_id', $userId)->exists()) {
                $participant = ClubActivityParticipant::create([
                    'club_activity_id' => $activity->id,
                    'user_id' => $userId,
                    'status' => ClubActivityParticipantStatus::Invited,
                ]);
                $participant->load('user');
                $invited[] = $participant;
                $currentCount++;
            }
        }

        return [
            'invited_count' => count($invited),
            'participants' => $invited,
        ];
    }

    public function updateParticipantStatus(ClubActivityParticipant $participant, string $status): ClubActivityParticipant
    {
        return DB::transaction(function () use ($participant, $status) {
            $activity = $participant->activity;

            // If changing to Accepted and has fee, create transaction
            if (
                $status === ClubActivityParticipantStatus::Accepted->value
                && $activity->fee_amount > 0
                && !$participant->wallet_transaction_id
            ) {
                $this->createFeeTransaction($participant, $activity);
            } else {
                $participant->update(['status' => $status]);
            }

            return $participant;
        });
    }

    public function approveRequest(ClubActivityParticipant $participant, int $approverId): ClubActivityParticipant
    {
        $activity = $participant->activity;
        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $approverId)->first();

        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            throw new \Exception('Chỉ admin/manager/secretary mới có quyền duyệt yêu cầu');
        }

        if ($participant->status !== ClubActivityParticipantStatus::Pending) {
            throw new \Exception('Chỉ có thể duyệt yêu cầu đang pending');
        }

        if ($activity->max_participants !== null && $activity->acceptedParticipants()->count() >= $activity->max_participants) {
            throw new \Exception('Sự kiện đã đủ số lượng người tham gia');
        }

        return DB::transaction(function () use ($participant, $activity) {
            if ($activity->fee_amount > 0) {
                $this->createFeeTransaction($participant, $activity);
            } else {
                $participant->update(['status' => ClubActivityParticipantStatus::Accepted]);
            }

            return $participant;
        });
    }

    public function rejectRequest(ClubActivityParticipant $participant, int $rejecterId): ClubActivityParticipant
    {
        $activity = $participant->activity;
        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $rejecterId)->first();

        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            throw new \Exception('Chỉ admin/manager/secretary mới có quyền từ chối yêu cầu');
        }

        if ($participant->status !== ClubActivityParticipantStatus::Pending) {
            throw new \Exception('Chỉ có thể từ chối yêu cầu đang pending');
        }

        $participant->decline();

        return $participant;
    }

    public function acceptInvite(ClubActivityParticipant $participant, int $userId): ClubActivityParticipant
    {
        if ($participant->user_id !== $userId) {
            throw new \Exception('Chỉ có thể chấp nhận lời mời của chính mình');
        }

        if ($participant->status !== ClubActivityParticipantStatus::Invited) {
            throw new \Exception('Chỉ có thể chấp nhận lời mời đang invited');
        }

        $activity = $participant->activity;

        if ($activity->max_participants !== null && $activity->acceptedParticipants()->count() >= $activity->max_participants) {
            throw new \Exception('Sự kiện đã đủ số lượng người tham gia');
        }

        return DB::transaction(function () use ($participant, $activity) {
            if ($activity->fee_amount > 0) {
                $this->createFeeTransaction($participant, $activity);
            } else {
                $participant->update(['status' => ClubActivityParticipantStatus::Accepted]);
            }

            return $participant;
        });
    }

    public function declineInvite(ClubActivityParticipant $participant, int $userId): ClubActivityParticipant
    {
        if ($participant->user_id !== $userId) {
            throw new \Exception('Chỉ có thể từ chối lời mời của chính mình');
        }

        if ($participant->status !== ClubActivityParticipantStatus::Invited) {
            throw new \Exception('Chỉ có thể từ chối lời mời đang invited');
        }

        $participant->decline();

        return $participant;
    }

    public function cancelRequest(ClubActivityParticipant $participant, int $userId): void
    {
        if ($participant->user_id !== $userId) {
            throw new \Exception('Chỉ có thể hủy yêu cầu của chính mình');
        }

        if ($participant->status !== ClubActivityParticipantStatus::Pending) {
            throw new \Exception('Chỉ có thể hủy yêu cầu đang pending');
        }

        $participant->delete();
    }

    public function withdraw(ClubActivityParticipant $participant, int $userId): array
    {
        if ($participant->user_id !== $userId) {
            throw new \Exception('Chỉ có thể rút khỏi sự kiện của chính mình');
        }

        $activity = $participant->activity;

        if (!$activity->isScheduled()) {
            throw new \Exception('Chỉ có thể rút khỏi sự kiện đang scheduled');
        }

        if ($participant->status !== ClubActivityParticipantStatus::Accepted) {
            throw new \Exception('Chỉ có thể rút khỏi sự kiện khi đã chấp nhận tham gia');
        }

        return DB::transaction(function () use ($participant, $activity) {
            $club = $activity->club;
            $mainWallet = $club->mainWallet;

            $hoursUntilStart = Carbon::now()->diffInHours($activity->start_time, false);
            $isBefore4Hours = $hoursUntilStart >= 4;

            $transaction = $participant->walletTransaction;

            if ($transaction && $mainWallet) {
                // Cancel the fee transaction
                $transaction->reject($participant->user_id);

                // If withdrawn after 4 hours => create penalty transaction
                if (!$isBefore4Hours) {
                    $penaltyAmount = $activity->penalty_amount ?? 0;

                    if ($penaltyAmount > 0) {
                        ClubWalletTransaction::create([
                            'club_wallet_id' => $mainWallet->id,
                            'direction' => ClubWalletTransactionDirection::In,
                            'amount' => $penaltyAmount,
                            'source_type' => ClubWalletTransactionSourceType::ActivityPenalty,
                            'source_id' => $activity->id,
                            'payment_method' => PaymentMethod::BankTransfer,
                            'status' => ClubWalletTransactionStatus::Pending,
                            'description' => "Phạt rút khỏi sự kiện: {$activity->title} (rút sau 4 tiếng - phí phạt " . number_format($penaltyAmount, 0, ',', '.') . " VND)",
                            'created_by' => $participant->user_id,
                        ]);
                    }
                }
            }

            // Update participant status to declined
            $participant->decline();

            $message = $isBefore4Hours
                ? 'Đã rút khỏi sự kiện. Khoản thu đã được hủy.'
                : "Đã rút khỏi sự kiện. Khoản thu đã được hủy và tạo khoản thu phạt (" . number_format($activity->penalty_amount ?? 0, 0, ',', '.') . " VND) do rút sau 4 tiếng.";

            return [
                'participant' => $participant,
                'message' => $message,
            ];
        });
    }

    public function deleteParticipant(ClubActivityParticipant $participant, int $deleterId): void
    {
        $activity = $participant->activity;
        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $deleterId)->first();

        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            throw new \Exception('Chỉ admin/manager/secretary mới có quyền xóa');
        }

        $participant->delete();
    }

    public function checkIn(ClubActivity $activity, string $token, int $userId): ClubActivityParticipant
    {
        if ($activity->status === ClubActivityStatus::Cancelled) {
            throw new \Exception('Sự kiện đã bị hủy');
        }

        if (!$activity->check_in_token || $activity->check_in_token !== $token) {
            throw new \Exception('Mã check-in không hợp lệ');
        }

        $member = $activity->club->activeMembers()->where('user_id', $userId)->first();
        if (!$member) {
            throw new \Exception('Bạn không phải thành viên CLB');
        }

        $participant = ClubActivityParticipant::where('club_activity_id', $activity->id)
            ->where('user_id', $userId)
            ->with('user')
            ->first();

        if (!$participant) {
            throw new \Exception('Bạn chưa tham gia hoạt động này');
        }

        if ($participant->status === ClubActivityParticipantStatus::Attended) {
            return $participant; // Already checked in
        }

        if ($participant->status !== ClubActivityParticipantStatus::Accepted) {
            throw new \Exception('Chỉ có thể check-in khi đã được duyệt tham gia');
        }

        $participant->update([
            'status' => ClubActivityParticipantStatus::Attended,
            'checked_in_at' => now(),
        ]);

        return $participant;
    }

    public function getCheckInList(ClubActivity $activity, int $requesterId): array
    {
        $member = $activity->club->activeMembers()->where('user_id', $requesterId)->first();

        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            throw new \Exception('Chỉ admin/manager/secretary mới có quyền xem danh sách check-in');
        }

        $checkedIn = $activity->participants()
            ->where('status', ClubActivityParticipantStatus::Attended)
            ->with('user')
            ->get();

        $waiting = $activity->participants()
            ->where('status', ClubActivityParticipantStatus::Accepted)
            ->with('user')
            ->get();

        return [
            'checked_in' => $checkedIn,
            'waiting' => $waiting,
            'summary' => [
                'checked_in_count' => $checkedIn->count(),
                'waiting_count' => $waiting->count(),
            ],
        ];
    }

    private function createFeeTransaction(ClubActivityParticipant $participant, ClubActivity $activity): void
    {
        $club = $activity->club;
        $mainWallet = $club->mainWallet;

        if (!$mainWallet) {
            $participant->update(['status' => ClubActivityParticipantStatus::Accepted]);
            return;
        }

        $amount = $this->calculateFeeAmount($activity);

        $transaction = ClubWalletTransaction::create([
            'club_wallet_id' => $mainWallet->id,
            'direction' => ClubWalletTransactionDirection::In,
            'amount' => $amount,
            'source_type' => ClubWalletTransactionSourceType::Activity,
            'source_id' => $activity->id,
            'payment_method' => PaymentMethod::BankTransfer,
            'status' => ClubWalletTransactionStatus::Pending,
            'description' => "Phí tham gia sự kiện: {$activity->title}",
            'created_by' => $participant->user_id,
        ]);

        $participant->update([
            'status' => ClubActivityParticipantStatus::Accepted,
            'wallet_transaction_id' => $transaction->id,
        ]);
    }

    private function calculateFeeAmount(ClubActivity $activity): float
    {
        $feeSplitType = $activity->fee_split_type ?? ClubActivityFeeSplitType::Fixed;

        return $feeSplitType === ClubActivityFeeSplitType::Fixed
            ? (float) $activity->fee_amount
            : (float) $activity->fee_amount / max(1, (int) ($activity->max_participants ?? 1));
    }
}
