<?php

namespace App\Services\Club;

use App\Enums\ClubFundCollectionStatus;
use App\Enums\ClubFundContributionStatus;
use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Enums\ClubMembershipStatus;
use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionSourceType;
use App\Enums\ClubWalletTransactionStatus;
use App\Enums\ClubWalletType;
use App\Enums\PaymentMethod;
use App\Jobs\SendPushJob;
use App\Models\Club\Club;
use App\Models\Club\ClubFundCollection;
use App\Models\Club\ClubFundContribution;
use App\Models\Club\ClubMember;
use App\Models\User;
use App\Notifications\ClubFundContributionApprovedNotification;
use App\Notifications\ClubFundContributionRejectedNotification;
use App\Notifications\ClubFundContributionSubmittedNotification;
use App\Services\ImageOptimizationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ClubFundContributionService
{
    public function __construct(
        protected ImageOptimizationService $imageService,
        protected ClubWalletService $walletService
    ) {
    }

    public function getContributions(ClubFundCollection $collection, array $filters): LengthAwarePaginator
    {
        $query = $collection->contributions()->with(['user', 'walletTransaction']);

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function submitContribution(ClubFundCollection $collection, int $userId, $image, ?string $note = null): ClubFundContribution
    {
        $amountDue = (float) ($collection->amount_per_member ?? 0);

        if ($collection->status !== ClubFundCollectionStatus::Active) {
            $message = match ($collection->status) {
                ClubFundCollectionStatus::Pending => 'Mã QR này chưa được gắn với đợt thu. Vui lòng chờ admin tạo đợt thu và chọn mã QR này.',
                ClubFundCollectionStatus::Completed => 'Đợt thu đã kết thúc.',
                ClubFundCollectionStatus::Cancelled => 'Đợt thu đã bị hủy.',
                default => 'Đợt thu không còn hoạt động.',
            };
            throw new \Exception($message);
        }

        $existingPending = $collection->contributions()
            ->where('user_id', $userId)
            ->where('status', ClubFundContributionStatus::Pending)
            ->first();

        if ($existingPending) {
            throw new \Exception('Đóng góp của bạn đang chờ xác nhận');
        }

        $assigned = $collection->assignedMembers()->where('user_id', $userId)->first();
        if ($assigned) {
            $amountDue = (float) ($assigned->pivot?->amount_due ?? $amountDue);
        }

        if ($amountDue <= 0) {
            throw new \Exception('Số tiền cần đóng không hợp lệ');
        }

        $receiptUrl = $this->imageService->optimizeThumbnail($image, 'fund_contribution_receipts', 90);

        $contribution = ClubFundContribution::create([
            'club_fund_collection_id' => $collection->id,
            'user_id' => $userId,
            'amount' => $amountDue,
            'receipt_url' => $receiptUrl,
            'note' => $note,
            'status' => ClubFundContributionStatus::Pending,
        ]);

        $club = $collection->club;
        $submitter = User::find($userId);
        $financeManagerUserIds = ClubMember::where('club_id', $club->id)
            ->where('membership_status', ClubMembershipStatus::Joined)
            ->where('status', ClubMemberStatus::Active)
            ->whereIn('role', [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary, ClubMemberRole::Treasurer])
            ->pluck('user_id')
            ->unique()
            ->filter(fn ($id) => $id !== $userId);

        if ($submitter && $club) {
            $collectionTitle = $collection->title ?: $collection->description ?: 'Đợt thu quỹ';
            $message = ($submitter->full_name ?: $submitter->email) . " đã nộp thanh toán cho khoản thu {$collectionTitle} tại CLB {$club->name}";
            $amountStr = number_format($contribution->amount, 0, ',', '.') . ' VND';

            foreach ($financeManagerUserIds as $recipientId) {
                $user = User::find($recipientId);
                if ($user) {
                    $user->notify(new ClubFundContributionSubmittedNotification($club, $collection, $contribution, $submitter));
                    SendPushJob::dispatch($user->id, 'Yêu cầu thanh toán mới', $message . ' - ' . $amountStr, [
                        'type' => 'CLUB_FUND_CONTRIBUTION_SUBMITTED',
                        'club_id' => (string) $club->id,
                        'club_fund_collection_id' => (string) $collection->id,
                        'club_fund_contribution_id' => (string) $contribution->id,
                    ]);
                }
            }
        }

        return $contribution;
    }

    public function confirmContribution(ClubFundContribution $contribution, int $confirmerId): ClubFundContribution
    {
        if ($contribution->status !== ClubFundContributionStatus::Pending) {
            throw new \Exception('Chỉ có thể xác nhận đóng góp đang pending');
        }

        $contribution = DB::transaction(function () use ($contribution, $confirmerId) {
            $contribution->confirm();

            $club = $contribution->fundCollection->club;
            $mainWallet = $club->mainWallet;
            if (!$mainWallet) {
                $mainWallet = $this->walletService->createWallet($club, [
                    'type' => ClubWalletType::Main,
                    'currency' => 'VND',
                ]);
            }

            if ($contribution->walletTransaction) {
                $contribution->walletTransaction->confirm($confirmerId);
            } else {
                $collection = $contribution->fundCollection;
                $description = $collection->title ?: $collection->description ?: 'Đợt thu quỹ';
                $transaction = $mainWallet->transactions()->create([
                    'direction' => ClubWalletTransactionDirection::In,
                    'amount' => $contribution->amount,
                    'source_type' => ClubWalletTransactionSourceType::FundCollection,
                    'source_id' => $contribution->id,
                    'payment_method' => PaymentMethod::Other,
                    'status' => ClubWalletTransactionStatus::Confirmed,
                    'description' => $description,
                    'created_by' => $contribution->user_id,
                    'confirmed_by' => $confirmerId,
                    'confirmed_at' => now(),
                ]);
                $contribution->update(['wallet_transaction_id' => $transaction->id]);
            }

            return $contribution->fresh();
        });

        $user = $contribution->user;
        $collection = $contribution->fundCollection;
        $club = $collection->club;
        if ($user && $club) {
            $collectionTitle = $collection->title ?: $collection->description ?: 'Đợt thu quỹ';
            $message = "Yêu cầu thanh toán của bạn cho khoản thu {$collectionTitle} đã được chấp nhận";
            $user->notify(new ClubFundContributionApprovedNotification($club, $collection, $contribution));
            SendPushJob::dispatch($user->id, 'Thanh toán đã được chấp nhận', $message, [
                'type' => 'CLUB_FUND_CONTRIBUTION_APPROVED',
                'club_id' => (string) $club->id,
                'club_fund_collection_id' => (string) $collection->id,
                'club_fund_contribution_id' => (string) $contribution->id,
            ]);
        }

        return $contribution;
    }

    public function rejectContribution(ClubFundContribution $contribution, ?string $rejectionReason = null): ClubFundContribution
    {
        $contribution->reject();

        $user = $contribution->user;
        $collection = $contribution->fundCollection;
        $club = $collection->club;
        if ($user && $club) {
            $collectionTitle = $collection->title ?: $collection->description ?: 'Đợt thu quỹ';
            $message = "Yêu cầu thanh toán của bạn cho khoản thu {$collectionTitle} đã bị từ chối";
            if ($rejectionReason) {
                $message .= ": {$rejectionReason}";
            }
            $user->notify(new ClubFundContributionRejectedNotification($club, $collection, $contribution, $rejectionReason));
            SendPushJob::dispatch($user->id, 'Thanh toán đã bị từ chối', $message, [
                'type' => 'CLUB_FUND_CONTRIBUTION_REJECTED',
                'club_id' => (string) $club->id,
                'club_fund_collection_id' => (string) $collection->id,
                'club_fund_contribution_id' => (string) $contribution->id,
            ]);
        }

        return $contribution;
    }
}
