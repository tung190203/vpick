<?php

namespace App\Notifications;

use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Club $club,
        public ClubMember $member,
        public string $inviterName
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "Bạn được mời tham gia CLB {$this->club->name} bởi {$this->inviterName}";

        return [
            'club_id' => $this->club->id,
            'club_name' => $this->club->name,
            'club_member_id' => $this->member->id,
            'title' => 'Lời mời tham gia CLB',
            'message' => $message,
            'invited_by' => $this->member->invited_by,
            'inviter_name' => $this->inviterName,
            'role' => $this->member->role->value,
        ];
    }
}
