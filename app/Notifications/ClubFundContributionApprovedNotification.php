<?php

namespace App\Notifications;

use App\Models\Club\Club;
use App\Models\Club\ClubFundCollection;
use App\Models\Club\ClubFundContribution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubFundContributionApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Club $club,
        public ClubFundCollection $collection,
        public ClubFundContribution $contribution
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $collectionTitle = $this->collection->title ?: $this->collection->description ?: 'Đợt thu quỹ';
        $message = "Yêu cầu thanh toán của bạn cho khoản thu {$collectionTitle} đã được chấp nhận";

        return [
            'club_id' => $this->club->id,
            'club_fund_collection_id' => $this->collection->id,
            'club_fund_contribution_id' => $this->contribution->id,
            'collection_title' => $collectionTitle,
            'amount' => (float) $this->contribution->amount,
            'title' => 'Thanh toán đã được chấp nhận',
            'message' => $message,
        ];
    }
}
