<?php

namespace App\Notifications;

use App\Models\Club\Club;
use App\Models\Club\ClubActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubActivityInvitationNotification extends Notification implements ShouldQueue
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
        $message = "Bạn được mời tham gia sự kiện {$this->activity->title} tại CLB {$this->club->name}";

        return [
            'club_id' => $this->club->id,
            'club_name' => $this->club->name,
            'club_activity_id' => $this->activity->id,
            'activity_title' => $this->activity->title,
            'title' => 'Lời mời tham gia sự kiện',
            'message' => $message,
        ];
    }
}
