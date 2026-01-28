<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserListResource;

class ClubNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'club_notification_type_id' => $this->club_notification_type_id,
            'title' => $this->title,
            'content' => $this->content,
            'attachment_url' => $this->attachment_url,
            'priority' => $this->priority,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'is_pinned' => $this->is_pinned,
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'sent_at' => $this->sent_at?->toISOString(),
            'created_by' => $this->created_by,
            'read_count' => $this->when(isset($this->read_count), $this->read_count),
            'unread_count' => $this->when(isset($this->unread_count), $this->unread_count),
            'type' => $this->whenLoaded('type'),
            'creator' => new UserListResource($this->whenLoaded('creator')),
            'recipients' => ClubNotificationRecipientResource::collection($this->whenLoaded('recipients')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
