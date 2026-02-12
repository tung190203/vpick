<?php

namespace App\Notifications;

use App\Models\Club\Club;
use App\Models\Club\ClubFundCollection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubFundCollectionCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Club $club,
        public ClubFundCollection $collection
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $collectionTitle = $this->collection->title ?: $this->collection->description ?: 'Đợt thu quỹ';
        $message = "Đợt thu {$collectionTitle} tại CLB {$this->club->name} đã bị hủy";

        return [
            'club_id' => $this->club->id,
            'club_fund_collection_id' => $this->collection->id,
            'collection_title' => $collectionTitle,
            'title' => 'Đợt thu đã bị hủy',
            'message' => $message,
        ];
    }
}
