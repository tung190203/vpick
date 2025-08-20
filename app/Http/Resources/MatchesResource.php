<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchesResource extends JsonResource
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
            'group' => $this->group?->name,
            'round' => $this->round,
            'scheduled_at' => $this->scheduled_at,
            'status' => $this->status,

            'participant1' => new ParticipantResource($this->whenLoaded('participant1')),
            'participant2' => new ParticipantResource($this->whenLoaded('participant2')),

            'referee' => $this->whenLoaded('referee', function () {
                return [
                    'id' => $this->referee->id,
                    'name' => $this->referee->full_name,
                ];
            }),
        ];
    }
}
