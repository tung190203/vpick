<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;

class MiniTournamentReminder extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $miniTournament;

    public function __construct($miniTournament)
    {
        $this->miniTournament = $miniTournament;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'mini_tournament_id' => $this->miniTournament->id,
            'title' => $this->miniTournament->name,
            'starts_at' => $this->miniTournament->starts_at,
            'message' => "Kèo đấu '{$this->miniTournament->name}' sẽ bắt đầu lúc {$this->miniTournament->starts_at}",
        ];
    }
}
