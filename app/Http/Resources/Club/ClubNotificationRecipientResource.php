<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserListResource;

class ClubNotificationRecipientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_notification_id' => $this->club_notification_id,
            'user_id' => $this->user_id,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toISOString(),
            'user' => new UserListResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
