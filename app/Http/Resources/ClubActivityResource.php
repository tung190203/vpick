<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'mini_tournament_id' => $this->mini_tournament_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'is_recurring' => $this->is_recurring,
            'recurring_schedule' => $this->recurring_schedule,
            'start_time' => $this->start_time->toISOString(),
            'end_time' => $this->end_time?->toISOString(),
            'location' => $this->location,
            'reminder_minutes' => $this->reminder_minutes,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'participants_count' => $this->whenLoaded('participants', fn() => $this->participants->count()),
            'accepted_count' => $this->whenLoaded('participants', fn() => $this->participants->where('status', 'accepted')->count()),
            'participants' => ClubActivityParticipantResource::collection($this->whenLoaded('participants')),
            'creator' => new UserListResource($this->whenLoaded('creator')),
            'mini_tournament' => $this->whenLoaded('miniTournament'),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
