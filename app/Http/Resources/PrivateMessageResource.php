<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrivateMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'sender' => new UserListResource($this->whenLoaded('sender')),
            'receiver_id' => $this->receiver_id,
            'message' => $this->message,
            'attachment_url' => $this->attachment_url,
            'attachment_type' => $this->attachment_type,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at ? $this->read_at->toDateTimeString() : null,
            'is_own' => $this->sender_id === auth()->id(),
        ];
    }
}
