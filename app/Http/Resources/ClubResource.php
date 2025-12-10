<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserListResource;

class ClubResource extends JsonResource
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
            'members' => UserListResource::collection($this->whenLoaded('members')),
            'quantity_members' => $this->whenLoaded('members', fn() => $this->members->count(), 0),
            'highest_score' => $this->whenLoaded('members', fn() => $this->members->max(fn($m) => $m->vnduprScores->first()?->score_value ?? 0), 0),
            'is_member' => $this->whenLoaded('members', fn() => $this->members->contains(fn($m) => $m->id === auth()->id()), false),
        ];
    }
}
