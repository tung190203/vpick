<?php

namespace App\Notifications;

use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubRoleChangeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Club $club,
        public ClubMember $member,
        public string $newRoleLabel,
        public int $updaterId
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "Bạn được bổ nhiệm làm {$this->newRoleLabel} trong CLB {$this->club->name}";

        return [
            'club_id' => $this->club->id,
            'club_name' => $this->club->name,
            'club_member_id' => $this->member->id,
            'title' => 'Bạn được bổ nhiệm làm ' . $this->newRoleLabel,
            'message' => $message,
            'new_role' => $this->member->role->value,
            'new_role_label' => $this->newRoleLabel,
            'updated_by' => $this->updaterId,
        ];
    }
}
