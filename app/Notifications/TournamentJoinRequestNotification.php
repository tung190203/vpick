<?php

namespace App\Notifications;

use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TournamentJoinRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $participant;
    public function __construct(Participant $participant)
    {
        $this->participant = $participant;
    }
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'tournament_id' => $this->participant->tournament_id,
            'participant_id' => $this->participant->id,
            'title' => 'Có yêu cầu tham gia giải đấu',
            'message' => $this->participant->user->full_name . " muốn tham gia giải đấu " . $this->participant->tournament->name,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return [
            'data' => $this->toDatabase($notifiable)
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tournament_id' => $this->participant->tournament_id,
            'participant_id' => $this->participant->id,
            'message' => $this->participant->user->full_name . " muốn tham gia giải đấu " . $this->participant->tournament->name,
        ];
    }
}
