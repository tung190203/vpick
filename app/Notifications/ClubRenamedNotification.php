<?php

namespace App\Notifications;

use App\Models\Club\Club;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ClubRenamedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Club $club,
        public string $oldName,
        public string $newName
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $message = "CLB {$this->oldName} đã được quản trị viên đổi tên thành {$this->newName}";

        return [
            'club_id' => $this->club->id,
            'club_name' => $this->newName,
            'old_name' => $this->oldName,
            'new_name' => $this->newName,
            'title' => 'CLB đã đổi tên',
            'message' => $message,
        ];
    }
}
