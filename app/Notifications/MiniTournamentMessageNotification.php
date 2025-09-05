<?php

namespace App\Notifications;

use App\Models\MiniTournamentMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MiniTournamentMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $message;

    public function __construct(MiniTournamentMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        // Lưu DB và broadcast realtime
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'mini_tournament_id' => $this->message->mini_tournament_id,
            'message_id' => $this->message->id,
            'sender_id' => $this->message->user_id,
            'sender_name' => $this->message->user->name,
            'type' => $this->message->type,
            'content' => $this->message->content,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return [
            'data' => $this->toDatabase($notifiable)
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'mini_tournament_id' => $this->message->mini_tournament_id,
            'message_id' => $this->message->id,
            'sender_id' => $this->message->user_id,
            'sender_name' => $this->message->user->name,
            'type' => $this->message->type,
            'content' => $this->message->content,
        ];
    }
}
