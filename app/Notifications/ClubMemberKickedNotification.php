<?php

namespace App\Notifications;

use App\Models\Club\Club;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubMemberKickedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Club $club
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "Bạn đã bị đuổi khỏi CLB {$this->club->name}";

        return [
            'club_id' => $this->club->id,
            'club_name' => $this->club->name,
            'title' => 'Bạn đã bị đuổi khỏi CLB',
            'message' => $message,
        ];
    }
}
