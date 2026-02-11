<?php

namespace App\Notifications;

use App\Models\Club\ClubFundCollection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubFundCollectionReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ClubFundCollection $collection,
        public string $collectionTitle,
        public string $clubName,
        public ?float $amountDue = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "Bạn được nhắc nhở đóng khoản thu {$this->collectionTitle} ở CLB {$this->clubName}";

        return [
            'club_id' => $this->collection->club_id,
            'club_fund_collection_id' => $this->collection->id,
            'title' => 'Nhắc nhở đóng khoản thu',
            'message' => $message,
            'collection_title' => $this->collectionTitle,
            'club_name' => $this->clubName,
            'amount_due' => $this->amountDue,
            'reminded_by' => auth()->id(),
        ];
    }
}
