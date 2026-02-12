<?php

namespace App\Notifications;

use App\Models\Club\Club;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubJoinRequestApprovedNotification extends Notification implements ShouldQueue
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
        $message = "Bạn đã được chấp nhận tham gia CLB {$this->club->name}";

        return [
            'club_id' => $this->club->id,
            'club_name' => $this->club->name,
            'title' => 'Yêu cầu tham gia CLB đã được duyệt',
            'message' => $message,
        ];
    }
}
