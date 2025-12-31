<?php

namespace App\Notifications;

use App\Models\MiniParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class MiniTournamentJoinConfirmedNotification extends Notification implements ShouldQueue
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

    public function via($notifiable)
    {
        return ['database', 'broadcast']; // hoặc thêm mail nếu muốn
    }

    public function toDatabase($notifiable)
    {
        return [
            'participant_id' => $this->participant->id,
            'mini_tournament_id' => $this->participant->mini_tournament_id,
            'title' => 'Yêu cầu tham gia được duyệt',
            'message' => "Yêu cầu tham gia kèo đấu '{$this->participant->miniTournament->name}' của bạn đã được duyệt.",
            'confirmed_by' => auth()->id(),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'participant_id' => $this->participant->id,
            'mini_tournament_id' => $this->participant->mini_tournament_id,
            'title' => 'Yêu cầu tham gia được duyệt',
            'message' => "Yêu cầu tham gia kèo đấu '{$this->participant->miniTournament->name}' của bạn đã được duyệt.",
            'confirmed_by' => auth()->id(),
            'created_at' => now()->toDateTimeString(),
        ]);
    }

    public function toArray($notifiable)
    {
        return [
            'participant_id' => $this->participant->id,
            'message' => "Yêu cầu tham gia giải đấu '{$this->participant->miniTournament->name}' của bạn đã được duyệt.",
            'confirmed_by' => auth()->id(),
        ];
    }
}
