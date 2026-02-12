<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class ClubNotificationBase extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    protected static function payload(string $title, string $message, array $redirect = []): array
    {
        return array_merge(
            [
                'title' => $title,
                'message' => $message,
            ],
            $redirect
        );
    }
}
