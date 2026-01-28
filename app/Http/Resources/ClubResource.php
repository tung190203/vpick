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
            'skill_level' => $this->whenLoaded('members', function () {
                $scores = $this->members
                    ->map(fn($member) => $member->user?->vnduprScores?->max('score_value'))
                    ->filter(fn($score) => $score !== null);

                if ($scores->isEmpty()) {
                    return null;
                }

                return [
                    'min' => round($scores->min(), 1),
                    'max' => round($scores->max(), 1),
                ];
            }, null),
            'is_member' => $this->whenLoaded('members', fn() => $this->members->contains(fn($m) => $m->user_id === auth()->id()), false),
            'profile' => $this->whenLoaded('profile'),
            'wallets' => $this->whenLoaded('wallets'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
