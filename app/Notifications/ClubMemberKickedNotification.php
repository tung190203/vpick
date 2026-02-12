<?php

namespace App\Notifications;

use App\Models\Club\Club;

class ClubMemberKickedNotification extends ClubNotificationBase
{
    public function __construct(
        public Club $club
    ) {
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "Bạn đã bị đuổi khỏi CLB {$this->club->name}";

        return self::payload('Bạn đã bị đuổi khỏi CLB', $message, [
            'club_id' => $this->club->id,
        ]);
    }
}
