<?php

namespace App\Notifications;

use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TournamentRemovedNotification extends Notification implements ShouldQueue
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

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toDatabase($notifiable)
    {
        return [
            'participant_id' => $this->participant->id,
            'tournament_id' => $this->participant->tournament_id,
            'title' => 'Bạn đã bị xóa khỏi giải đấu',
            'message' => "Bạn đã bị xóa khỏi giải đấu '{$this->participant->tournament->name}'",
            'removed_by' => auth()->id(),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'participant_id' => $this->participant->id,
            'tournament_id' => $this->participant->tournament_id,
            'title' => 'Bạn đã bị xóa khỏi giải đấu',
            'message' => "Bạn đã bị xóa khỏi giải đấu '{$this->participant->tournament->name}'",
            'removed_by' => auth()->id(),
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
            'participant_id' => $this->participant->id,
            'message' => "Bạn đã bị xóa khỏi giải đấu: {$this->participant->tournament->name}",
            'removed_by' => auth()->id(),
        ];
    }
}
