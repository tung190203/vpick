<?php

namespace App\Notifications;

use App\Models\Club\Club;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubJoinRequestRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Club $club,
        public ?string $rejectionReason = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "Yêu cầu tham gia CLB {$this->club->name} đã bị từ chối";
        if ($this->rejectionReason) {
            $message .= ": {$this->rejectionReason}";
        }

        return [
            'club_id' => $this->club->id,
            'club_name' => $this->club->name,
            'title' => 'Yêu cầu tham gia CLB đã bị từ chối',
            'message' => $message,
            'rejection_reason' => $this->rejectionReason,
        ];
    }
}
