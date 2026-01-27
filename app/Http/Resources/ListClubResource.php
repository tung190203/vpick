<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListClubResource extends JsonResource
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
            'name' => $this->name,
            'location' => $this->location,
            'logo_url' => $this->logo_url,
            'created_by' => $this->created_by,
            'quantity_members' => $this->whenLoaded('members', fn() => $this->members->count(), 0),
            'highest_score' => $this->whenLoaded('members', fn() => $this->members->max(fn($m) => $m->user?->vnduprScores?->first()?->score_value ?? 0), 0),
        ];
    }
}
