<?php

namespace App\Services\Club;

use App\Enums\ClubFundCollectionStatus;
use App\Enums\ClubFundContributionStatus;
use App\Models\Club\ClubFundCollection;
use App\Models\Club\ClubFundContribution;
use App\Services\ImageOptimizationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ClubFundContributionService
{
    public function __construct(
        protected ImageOptimizationService $imageService
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
        $amountDue = (float) ($collection->amount_per_member ?? $collection->target_amount);

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

        return ClubFundContribution::create([
            'club_fund_collection_id' => $collection->id,
            'user_id' => $userId,
            'amount' => $amountDue,
            'receipt_url' => $receiptUrl,
            'note' => $note,
            'status' => ClubFundContributionStatus::Pending,
        ]);
    }

    public function confirmContribution(ClubFundContribution $contribution, int $confirmerId): ClubFundContribution
    {
        if ($contribution->status !== ClubFundContributionStatus::Pending) {
            throw new \Exception('Chỉ có thể xác nhận đóng góp đang pending');
        }

        return DB::transaction(function () use ($contribution, $confirmerId) {
            $contribution->confirm();

            if ($contribution->walletTransaction) {
                $contribution->walletTransaction->confirm($confirmerId);
            }

            return $contribution;
        });
    }

    public function rejectContribution(ClubFundContribution $contribution): ClubFundContribution
    {
        $contribution->reject();
        return $contribution;
    }
}
