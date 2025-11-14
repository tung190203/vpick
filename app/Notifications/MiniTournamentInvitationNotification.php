<?php

namespace App\Notifications;

use App\Models\MiniTournament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class MiniTournamentInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $miniTournament;

    public function __construct(MiniTournament $miniTournament)
    {
        $this->miniTournament = $miniTournament;
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
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase($notifiable)
    {
        return [
            'mini_tournament_id' => $this->miniTournament->id,
            'title' => 'Bạn được mời tham gia kèo đấu',
            'message' => "Bạn được mời tham gia kèo đấu: {$this->miniTournament->name}",
            'invited_by' => auth()->id(),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'mini_tournament_id' => $this->miniTournament->id,
            'title' => 'Bạn được mời tham gia kèo đấu',
            'message' => "Bạn được mời tham gia kèo đấu: {$this->miniTournament->name}",
            'invited_by' => auth()->id(),
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'mini_tournament_id' => $this->miniTournament->id,
            'message' => "Bạn được mời tham gia kèo đấu: {$this->miniTournament->name}",
            'invited_by' => auth()->id(),
        ];
    }
}
