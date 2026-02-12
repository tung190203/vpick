<?php

namespace App\Notifications;

use App\Models\Club\Club;
use App\Models\Club\ClubFundCollection;
use App\Models\Club\ClubFundContribution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubFundContributionRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Club $club,
        public ClubFundCollection $collection,
        public ClubFundContribution $contribution,
        public ?string $rejectionReason = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $collectionTitle = $this->collection->title ?: $this->collection->description ?: 'Đợt thu quỹ';
        $message = "Yêu cầu thanh toán của bạn cho khoản thu {$collectionTitle} đã bị từ chối";
        if ($this->rejectionReason) {
            $message .= ": {$this->rejectionReason}";
        }

        return [
            'club_id' => $this->club->id,
            'club_fund_collection_id' => $this->collection->id,
            'club_fund_contribution_id' => $this->contribution->id,
            'collection_title' => $collectionTitle,
            'amount' => (float) $this->contribution->amount,
            'rejection_reason' => $this->rejectionReason,
            'title' => 'Thanh toán đã bị từ chối',
            'message' => $message,
        ];
    }
}
