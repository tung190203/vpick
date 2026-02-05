<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lightweight resource for activity list view
 * Only includes fields needed for displaying activity cards
 */
class ClubActivityListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status,
            'start_time' => $this->start_time?->toISOString(),
            'end_time' => $this->end_time?->toISOString(),
            'location' => $this->address, // Using address field for location
            'max_participants' => $this->max_participants !== null ? (int) $this->max_participants : null,
            'participants_count' => $this->whenLoaded('participants', fn() => $this->participants->count()),
            // Only return participant user_ids for checking if current user is registered
            'participant_user_ids' => $this->whenLoaded('participants',
                fn() => $this->participants->pluck('user_id')->toArray()
            ),
        ];
    }
}
