<?php

namespace App\Notifications;

use App\Models\Participant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class TournamentJoinConfirmedNotification extends Notification implements ShouldQueue
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

    public function toDatabase(object $notifiable): array
    {
        return [
            'participant_id' => $this->participant->id,
            'tournament_id' => $this->participant->tournament_id,
            'title' => 'Yêu cầu tham gia được duyệt',
            'message' => "Yêu cầu tham gia giải đấu '{$this->participant->tournament->name}' của bạn đã được duyệt.",
            'confirmed_by' => auth()->id(),
        ];
    }

    public function toBroadcast(object $notifiable)
    {
        return new BroadcastMessage([
            'participant_id' => $this->participant->id,
            'tournament_id' => $this->participant->tournament_id,
            'title' => 'Yêu cầu tham gia được duyệt',
            'message' => "Yêu cầu tham gia giải đấu '{$this->participant->tournament->name}' của bạn đã được duyệt.",
            'confirmed_by' => auth()->id(),
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
            'participant_id' => $this->participant->id,
            'message' => "Yêu cầu tham gia giải đấu '{$this->participant->tournament->name}' của bạn đã được duyệt.",
            'confirmed_by' => auth()->id(),
        ];
    }
}
