<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserListResource;
use App\Http\Resources\Club\ClubMemberResource;

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
            'status' => $this->status,
            'created_by' => $this->created_by,
            'members' => ClubMemberResource::collection($this->whenLoaded('members')),
            'quantity_members' => $this->whenLoaded('members', fn() => $this->members->count(), 0),
            'highest_score' => $this->whenLoaded('members', fn() => $this->members->max(fn($m) => $m->user?->vnduprScores?->first()?->score_value ?? 0), 0),
            'is_member' => $this->whenLoaded('members', fn() => $this->members->contains(fn($m) => $m->user_id === auth()->id()), false),
            'profile' => $this->whenLoaded('profile'),
            'wallets' => $this->whenLoaded('wallets'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
