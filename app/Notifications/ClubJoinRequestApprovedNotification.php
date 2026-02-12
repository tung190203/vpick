<?php

namespace App\Notifications;

use App\Models\Club\Club;

class ClubJoinRequestApprovedNotification extends ClubNotificationBase
{
    public function __construct(
        public Club $club
    ) {
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "Bạn đã được chấp nhận tham gia CLB {$this->club->name}";

        return self::payload('Yêu cầu tham gia CLB đã được duyệt', $message, [
            'club_id' => $this->club->id,
        ]);
    }
}
