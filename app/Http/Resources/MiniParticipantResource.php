<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MiniParticipantResource extends JsonResource
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
            'type' => $this->type,
            'is_confirmed' => (bool) $this->is_confirmed,
            'user' => $this->when($this->type === 'user', new UserListResource($this->user)),
            'team' => $this->when(
                $this->type === 'team' && $this->relationLoaded('team'),
                fn() => [
                    'id' => $this->team->id,
                    'name' => $this->team->name,
                    'members' => TeamMemberResource::collection($this->team->members),
                ]
            ),
        ];
    }
}
