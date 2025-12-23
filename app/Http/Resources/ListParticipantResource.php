<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListParticipantResource extends JsonResource
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
            'tournament_id' => $this->tournament_id,
            'is_confirmed' => $this->is_confirmed,
            'is_invite_by_organizer' => $this->is_invite_by_organizer,
            'user' => new UserListResource($this->whenLoaded('user')),
        ];
    }
}
