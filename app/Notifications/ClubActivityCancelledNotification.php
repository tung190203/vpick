<?php

namespace App\Notifications;

use App\Models\Club\Club;
use App\Models\Club\ClubActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubActivityCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Club $club,
        public ClubActivity $activity
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "Sự kiện {$this->activity->title} tại CLB {$this->club->name} đã bị hủy";

        return [
            'club_id' => $this->club->id,
            'club_name' => $this->club->name,
            'club_activity_id' => $this->activity->id,
            'activity_title' => $this->activity->title,
            'title' => 'Sự kiện đã bị hủy',
            'message' => $message,
        ];
    }
}
