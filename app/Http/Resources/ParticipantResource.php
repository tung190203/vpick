<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
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
            'type' => $this->user_id ? 'user' : 'team',
            'name' => $this->user?->full_name ?? $this->team?->name,
            'members' => $this->when(
                $this->relationLoaded('team') && $this->team?->relationLoaded('members'),
                fn() => UserListResource::collection($this->team->members)
            ),
        ];
    }
}
