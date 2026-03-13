<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class MiniTournamentRemovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $participantData;
    protected $removedBy;

    public function __construct($participantData, $removedBy = null)
    {
        $this->participantData = $participantData;
        $this->removedBy = $removedBy;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'participant_id' => $this->participantData['id'],
            'mini_tournament_id' => $this->participantData['mini_tournament_id'],
            'title' => 'Bạn đã bị xóa khỏi kèo đấu',
            'message' => "Bạn đã bị xóa khỏi kèo đấu '{$this->participantData['tournament_name']}'",
            'removed_by' => $this->removedBy,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'participant_id' => $this->participantData['id'],
            'mini_tournament_id' => $this->participantData['mini_tournament_id'],
            'title' => 'Bạn đã bị xóa khỏi kèo đấu',
            'message' => "Bạn đã bị xóa khỏi kèo đấu '{$this->participantData['tournament_name']}'",
            'removed_by' => $this->removedBy,
        ]);
    }

    public function toArray($notifiable)
    {
        return [
            'participant_id' => $this->participantData['id'],
            'message' => "Bạn đã bị xóa khỏi kèo đấu: {$this->participantData['tournament_name']}",
            'removed_by' => $this->removedBy,
        ];
    }
}
