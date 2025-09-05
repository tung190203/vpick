<?php

namespace App\Notifications;

use App\Models\MiniParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MiniTournamentJoinRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $participant;

    public function __construct(MiniParticipant $participant)
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
        return ['database', 'broadcast']; // vừa lưu DB vừa realtime
    }

    public function toDatabase($notifiable): array
    {
        return [
            'mini_tournament_id' => $this->participant->mini_tournament_id,
            'participant_id' => $this->participant->id,
            'title' => 'Có yêu cầu tham gia giải đấu',
            'message' => $this->participant->type === 'user'
                ? "{$this->participant->user->name} muốn tham gia giải đấu {$this->participant->miniTournament->name}"
                : "{$this->participant->team->name} muốn tham gia giải đấu {$this->participant->miniTournament->name}",
        ];
    }

    public function toBroadcast($notifiable)
    {
        return [
            'data' => $this->toDatabase($notifiable)
        ];
    }

    public function toArray($notifiable): array
    {
        return [
            'mini_tournament_id' => $this->participant->mini_tournament_id,
            'participant_id' => $this->participant->id,
            'message' => $this->participant->type === 'user'
                ? "{$this->participant->user->name} muốn tham gia giải đấu {$this->participant->miniTournament->name}"
                : "{$this->participant->team->name} muốn tham gia giải đấu {$this->participant->miniTournament->name}",
        ];
    }
}
