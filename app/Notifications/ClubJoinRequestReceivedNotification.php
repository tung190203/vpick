<?php

namespace App\Notifications;

use App\Models\Club\Club;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubJoinRequestReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Club $club,
        public User $applicant
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $applicantName = $this->applicant->full_name ?: $this->applicant->email;
        $message = "Có yêu cầu tham gia mới từ {$applicantName} tại CLB {$this->club->name}";

        return [
            'club_id' => $this->club->id,
            'club_name' => $this->club->name,
            'applicant_id' => $this->applicant->id,
            'applicant_name' => $applicantName,
            'title' => 'Yêu cầu tham gia CLB mới',
            'message' => $message,
        ];
    }
}
