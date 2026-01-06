<?php

namespace App\Notifications;

use App\Models\Tournament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class TournamentInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $tournament;

    public function __construct(Tournament $tournament)
    {
        $this->tournament = $tournament;
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

    public function toDatabase($notifiable)
    {
        return [
            'tournament_id' => $this->tournament->id,
            'title' => 'Bạn được mời tham gia giải đấu',
            'message' => "Bạn được mời tham gia giải đấu: {$this->tournament->name}",
            'invited_by' => auth()->id(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'tournament_id' => $this->tournament->id,
            'title' => 'Bạn được mời tham gia giải đấu',
            'message' => "Bạn được mời tham gia giải đấu: {$this->tournament->name}",
            'invited_by' => auth()->id(),
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'mini_tournament_id' => $this->tournament->id,
            'message' => "Bạn được mời tham gia kèo đấu: {$this->tournament->name}",
            'invited_by' => auth()->id(),
        ];
    }
}
