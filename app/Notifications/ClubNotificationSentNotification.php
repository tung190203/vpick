<?php

namespace App\Notifications;

use App\Models\Club\Club;
use App\Models\Club\ClubNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubNotificationSentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Club $club,
        public ClubNotification $clubNotification
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'club_id' => $this->club->id,
            'club_name' => $this->club->name,
            'club_notification_id' => $this->clubNotification->id,
            'title' => $this->clubNotification->title,
            'message' => $this->clubNotification->content,
            'content' => $this->clubNotification->content,
            'attachment_url' => $this->clubNotification->attachment_url,
            'priority' => $this->clubNotification->priority?->value,
            'metadata' => $this->clubNotification->metadata,
            'created_by' => $this->clubNotification->created_by,
        ];
    }
}
