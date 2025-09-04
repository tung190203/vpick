<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $readerId;
    public $senderId;
    public $readAt;

    public function __construct($readerId, $senderId, $readAt)
    {
        $this->readerId = $readerId;
        $this->senderId = $senderId;
        $this->readAt = $readAt;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->senderId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'reader_id' => $this->readerId,
            'sender_id' => $this->senderId,
            'read_at' => $this->readAt,
        ];
    }
}
