<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubActivityParticipantStatus;
use App\Enums\ClubMemberRole;
use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionSourceType;
use App\Enums\ClubWalletTransactionStatus;
use App\Enums\PaymentMethod;
use App\Helpers\ResponseHelper;
use App\Http\Resources\Club\ClubActivityParticipantResource;
use App\Models\Club\Club;
use App\Models\Club\ClubActivity;
use App\Models\Club\ClubActivityParticipant;
use App\Models\Club\ClubWalletTransaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ClubActivityParticipantController extends Controller
{
    public function index(Request $request, $clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::enum(ClubActivityParticipantStatus::class)],
        ]);

        $query = $activity->participants()->with('user');

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $participants = $query->get();

        $data = [
            'participants' => ClubActivityParticipantResource::collection($participants),
            'total' => $participants->count(),
            'pending_count' => $participants->where('status', ClubActivityParticipantStatus::Pending)->count(),
            'invited_count' => $participants->where('status', ClubActivityParticipantStatus::Invited)->count(),
            'accepted_count' => $participants->where('status', ClubActivityParticipantStatus::Accepted)->count(),
        ];
        return ResponseHelper::success($data, 'Lấy danh sách người tham gia thành công');
    }

    public function store(Request $request, $clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $userId)->first();
        if (!$member) {
            return ResponseHelper::error('Bạn không phải thành viên CLB', 403);
        }

        if ($activity->participants()->where('user_id', $userId)->exists()) {
            return ResponseHelper::error('Bạn đã tham gia hoạt động này', 409);
        }
        if ($activity->max_participants !== null && $activity->participants()->count() >= $activity->max_participants) {
            return ResponseHelper::error('Sự kiện đã đủ số lượng người tham gia', 422);
        }

        $participant = ClubActivityParticipant::create([
            'club_activity_id' => $activity->id,
            'user_id' => $userId,
            'status' => ClubActivityParticipantStatus::Pending,
        ]);

        $participant->load(['user', 'walletTransaction']);

        return ResponseHelper::success(new ClubActivityParticipantResource($participant), 'Đã gửi yêu cầu tham gia, chờ admin duyệt', 201);
    }

    public function invite(Request $request, $clubId, $activityId)
    {
        $activity = ClubActivity::where('club_id', $clubId)->findOrFail($activityId);
        $userId = auth()->id();

        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $userId)->first();
        if (!$member) {
            return ResponseHelper::error('Bạn không phải thành viên CLB', 403);
        }
        $canInvite = in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])
            || $activity->allow_member_invite;
        if (!$canInvite) {
            return ResponseHelper::error('Chỉ admin/manager/secretary hoặc khi sự kiện cho phép thành viên mời mới có quyền mời', 403);
        }

        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $currentCount = $activity->participants()->count();
        $maxParticipants = $activity->max_participants;

        $invited = [];
        foreach ($validated['user_ids'] as $userId) {
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

        $data = [
            'invited_count' => count($invited),
            'participants' => ClubActivityParticipantResource::collection(collect($invited)),
        ];
        return ResponseHelper::success($data, 'Đã mời thành công');
    }

    public function update(Request $request, $clubId, $activityId, $participantId)
    {
        $participant = ClubActivityParticipant::whereHas('activity', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->where('club_activity_id', $activityId)
            ->with(['activity', 'user'])
            ->findOrFail($participantId);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ClubActivityParticipantStatus::class)],
        ]);

        return DB::transaction(function () use ($participant, $validated) {
            $activity = $participant->activity;
            $club = $activity->club;
            $mainWallet = $club->mainWallet;

            // Nếu status là Accepted và activity có fee_amount > 0 và chưa có transaction
            if ($validated['status'] === ClubActivityParticipantStatus::Accepted->value
                && $activity->fee_amount > 0
                && !$participant->wallet_transaction_id
                && $mainWallet) {

                // fixed = phí/người; equal = tổng chia đều (fee_amount / max_participants hoặc 1)
                $feeSplitType = $activity->fee_split_type ?? 'fixed';
                $amount = $feeSplitType === 'fixed'
                    ? (float) $activity->fee_amount
                    : (float) $activity->fee_amount / max(1, (int) ($activity->max_participants ?? 1));

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

                // Lưu transaction_id vào participant
                $participant->update([
                    'status' => $validated['status'],
                    'wallet_transaction_id' => $transaction->id,
                ]);
            } else {
                $participant->update(['status' => $validated['status']]);
            }

            $participant->load(['user', 'walletTransaction']);

            return ResponseHelper::success(new ClubActivityParticipantResource($participant), 'Cập nhật trạng thái thành công');
        });
    }

    public function destroy($clubId, $activityId, $participantId)
    {
        $participant = ClubActivityParticipant::whereHas('activity', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->where('club_activity_id', $activityId)
            ->findOrFail($participantId);

        $userId = auth()->id();
        $activity = $participant->activity;
        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $userId)->first();

        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền xóa', 403);
        }

        $participant->delete();

        return ResponseHelper::success('Xóa người tham gia thành công');
    }

    /**
     * Admin approve yêu cầu tham gia (pending -> accepted)
     */
    public function approve($clubId, $activityId, $participantId)
    {
        $participant = ClubActivityParticipant::whereHas('activity', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->where('club_activity_id', $activityId)
            ->with(['activity', 'user'])
            ->findOrFail($participantId);

        $userId = auth()->id();
        $activity = $participant->activity;
        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $userId)->first();

        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền duyệt yêu cầu', 403);
        }

        if ($participant->status !== ClubActivityParticipantStatus::Pending) {
            return ResponseHelper::error('Chỉ có thể duyệt yêu cầu đang pending', 422);
        }

        if ($activity->max_participants !== null && $activity->acceptedParticipants()->count() >= $activity->max_participants) {
            return ResponseHelper::error('Sự kiện đã đủ số lượng người tham gia', 422);
        }

        return DB::transaction(function () use ($participant, $activity) {
            $club = $activity->club;
            $mainWallet = $club->mainWallet;

            // Tạo transaction nếu có phí
            if ($activity->fee_amount > 0 && $mainWallet) {
                $feeSplitType = $activity->fee_split_type ?? 'fixed';
                $amount = $feeSplitType === 'fixed'
                    ? (float) $activity->fee_amount
                    : (float) $activity->fee_amount / max(1, (int) ($activity->max_participants ?? 1));

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
            } else {
                $participant->update(['status' => ClubActivityParticipantStatus::Accepted]);
            }

            $participant->load(['user', 'walletTransaction']);

            return ResponseHelper::success(new ClubActivityParticipantResource($participant), 'Đã duyệt yêu cầu tham gia');
        });
    }

    /**
     * Admin reject yêu cầu tham gia (pending -> declined)
     */
    public function reject($clubId, $activityId, $participantId)
    {
        $participant = ClubActivityParticipant::whereHas('activity', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->where('club_activity_id', $activityId)
            ->with(['activity', 'user'])
            ->findOrFail($participantId);

        $userId = auth()->id();
        $activity = $participant->activity;
        $club = $activity->club;
        $member = $club->activeMembers()->where('user_id', $userId)->first();

        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền từ chối yêu cầu', 403);
        }

        if ($participant->status !== ClubActivityParticipantStatus::Pending) {
            return ResponseHelper::error('Chỉ có thể từ chối yêu cầu đang pending', 422);
        }

        $participant->decline();
        $participant->load(['user', 'walletTransaction']);

        return ResponseHelper::success(new ClubActivityParticipantResource($participant), 'Đã từ chối yêu cầu tham gia');
    }

    /**
     * Thành viên chấp nhận lời mời (invited -> accepted)
     */
    public function acceptInvite($clubId, $activityId, $participantId)
    {
        $participant = ClubActivityParticipant::whereHas('activity', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->where('club_activity_id', $activityId)
            ->with(['activity', 'user'])
            ->findOrFail($participantId);

        $userId = auth()->id();

        if ($participant->user_id !== $userId) {
            return ResponseHelper::error('Chỉ có thể chấp nhận lời mời của chính mình', 403);
        }

        if ($participant->status !== ClubActivityParticipantStatus::Invited) {
            return ResponseHelper::error('Chỉ có thể chấp nhận lời mời đang invited', 422);
        }

        $activity = $participant->activity;

        if ($activity->max_participants !== null && $activity->acceptedParticipants()->count() >= $activity->max_participants) {
            return ResponseHelper::error('Sự kiện đã đủ số lượng người tham gia', 422);
        }

        return DB::transaction(function () use ($participant, $activity) {
            $club = $activity->club;
            $mainWallet = $club->mainWallet;

            // Tạo transaction nếu có phí
            if ($activity->fee_amount > 0 && $mainWallet) {
                $feeSplitType = $activity->fee_split_type ?? 'fixed';
                $amount = $feeSplitType === 'fixed'
                    ? (float) $activity->fee_amount
                    : (float) $activity->fee_amount / max(1, (int) ($activity->max_participants ?? 1));

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
            } else {
                $participant->update(['status' => ClubActivityParticipantStatus::Accepted]);
            }

            $participant->load(['user', 'walletTransaction']);

            return ResponseHelper::success(new ClubActivityParticipantResource($participant), 'Đã chấp nhận tham gia sự kiện');
        });
    }

    /**
     * Thành viên từ chối lời mời (invited -> declined)
     */
    public function declineInvite($clubId, $activityId, $participantId)
    {
        $participant = ClubActivityParticipant::whereHas('activity', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->where('club_activity_id', $activityId)
            ->with(['activity', 'user'])
            ->findOrFail($participantId);

        $userId = auth()->id();

        if ($participant->user_id !== $userId) {
            return ResponseHelper::error('Chỉ có thể từ chối lời mời của chính mình', 403);
        }

        if ($participant->status !== ClubActivityParticipantStatus::Invited) {
            return ResponseHelper::error('Chỉ có thể từ chối lời mời đang invited', 422);
        }

        $participant->decline();
        $participant->load(['user', 'walletTransaction']);

        return ResponseHelper::success(new ClubActivityParticipantResource($participant), 'Đã từ chối lời mời tham gia');
    }

    /**
     * Thành viên hủy yêu cầu tham gia của mình (pending -> xóa)
     */
    public function cancel($clubId, $activityId, $participantId)
    {
        $participant = ClubActivityParticipant::whereHas('activity', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->where('club_activity_id', $activityId)
            ->findOrFail($participantId);

        $userId = auth()->id();

        if ($participant->user_id !== $userId) {
            return ResponseHelper::error('Chỉ có thể hủy yêu cầu của chính mình', 403);
        }

        if ($participant->status !== ClubActivityParticipantStatus::Pending) {
            return ResponseHelper::error('Chỉ có thể hủy yêu cầu đang pending', 422);
        }

        $participant->delete();

        return ResponseHelper::success('Đã hủy yêu cầu tham gia');
    }

    /**
     * Participant rút khỏi sự kiện
     * - Trước 4 tiếng: hủy khoản thu đã tạo
     * - Sau 4 tiếng: hủy khoản thu đã tạo, tạo khoản thu nộp phạt
     */
    public function withdraw($clubId, $activityId, $participantId)
    {
        $participant = ClubActivityParticipant::whereHas('activity', function ($q) use ($clubId) {
            $q->where('club_id', $clubId);
        })->where('club_activity_id', $activityId)
            ->with(['activity', 'user', 'walletTransaction'])
            ->findOrFail($participantId);

        $userId = auth()->id();

        // Chỉ participant mới được rút khỏi sự kiện của chính mình
        if ($participant->user_id !== $userId) {
            return ResponseHelper::error('Chỉ có thể rút khỏi sự kiện của chính mình', 403);
        }

        $activity = $participant->activity;

        // Chỉ có thể rút khỏi sự kiện đang scheduled
        if (!$activity->isScheduled()) {
            return ResponseHelper::error('Chỉ có thể rút khỏi sự kiện đang scheduled', 422);
        }

        // Chỉ rút được khi đã accepted
        if ($participant->status !== ClubActivityParticipantStatus::Accepted) {
            return ResponseHelper::error('Chỉ có thể rút khỏi sự kiện khi đã chấp nhận tham gia', 422);
        }

        return DB::transaction(function () use ($participant, $activity, $clubId) {
            $club = $activity->club;
            $mainWallet = $club->mainWallet;

            // Tính thời gian còn lại đến sự kiện
            $hoursUntilStart = Carbon::now()->diffInHours($activity->start_time, false);
            $isBefore4Hours = $hoursUntilStart >= 4;

            // Tìm transaction qua wallet_transaction_id (chính xác)
            $transaction = $participant->walletTransaction;

            if ($transaction && $mainWallet) {
                // Hủy khoản thu đã tạo
                $transaction->reject($participant->user_id);

                // Nếu rút sau 4 tiếng => tạo khoản thu nộp phạt
                if (!$isBefore4Hours) {
                    // Tính phí phạt theo penalty_percentage của activity (mặc định 50%)
                    $penaltyPercentage = $activity->penalty_percentage ?? 50;
                    $penaltyAmount = $transaction->amount * ($penaltyPercentage / 100);

                    $penaltyTransaction = ClubWalletTransaction::create([
                        'club_wallet_id' => $mainWallet->id,
                        'direction' => ClubWalletTransactionDirection::In,
                        'amount' => $penaltyAmount,
                        'source_type' => ClubWalletTransactionSourceType::ActivityPenalty,
                        'source_id' => $activity->id,
                        'payment_method' => PaymentMethod::BankTransfer,
                        'status' => ClubWalletTransactionStatus::Pending,
                        'description' => "Phạt rút khỏi sự kiện: {$activity->title} (rút sau 4 tiếng - {$penaltyPercentage}% phí gốc)",
                        'created_by' => $participant->user_id,
                    ]);

                    // Cập nhật participant để link với penalty transaction (hoặc giữ nguyên transaction gốc)
                    // Có thể tạo field penalty_transaction_id nếu cần track riêng
                }
            }

            // Cập nhật status participant thành declined
            $participant->decline();

            $participant->load(['user', 'walletTransaction']);

            $message = $isBefore4Hours
                ? 'Đã rút khỏi sự kiện. Khoản thu đã được hủy.'
                : "Đã rút khỏi sự kiện. Khoản thu đã được hủy và tạo khoản thu phạt ({$activity->penalty_percentage}% phí gốc) do rút sau 4 tiếng.";

            return ResponseHelper::success(new ClubActivityParticipantResource($participant), $message);
        });
    }
}
