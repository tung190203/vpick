<?php

namespace App\Notifications;

use App\Models\Club\Club;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubInvitationCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Club $club,
        public string $inviterName
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "Lời mời tham gia CLB {$this->club->name} đã bị hủy bởi {$this->inviterName}";

        return [
            'club_id' => $this->club->id,
            'club_name' => $this->club->name,
            'title' => 'Lời mời tham gia CLB đã bị hủy',
            'message' => $message,
            'inviter_name' => $this->inviterName,
        ];
    }
}
