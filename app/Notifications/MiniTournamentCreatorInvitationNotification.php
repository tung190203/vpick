<?php

namespace App\Notifications;

use App\Models\MiniParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class MiniTournamentCreatorInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $participant;

    public function __construct(MiniParticipant $participant)
    {
        $this->participant = $participant;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'participant_id' => $this->participant->id,
            'tournament_id' => $this->participant->mini_tournament_id,
            'title' => 'Bạn được mời tham gia giải đấu',
            'message' => "Bạn được mời tham gia giải đấu '{$this->participant->miniTournament->name}'",
            'invited_by' => auth()->id(),
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'participant_id' => $this->participant->id,
            'tournament_id' => $this->participant->mini_tournament_id,
            'title' => 'Bạn được mời tham gia giải đấu',
            'message' => "Bạn được mời tham gia giải đấu '{$this->participant->miniTournament->name}'",
            'invited_by' => auth()->id(),
        ]);
    }

    public function toArray($notifiable)
    {
        return [
            'participant_id' => $this->participant->id,
            'message' => "Bạn được mời tham gia giải đấu: {$this->participant->miniTournament->name}",
            'invited_by' => auth()->id(),
        ];
    }
}
