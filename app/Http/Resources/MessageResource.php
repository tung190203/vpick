<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
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
            'tournament_id' => $this->mini_tournament_id ?? $this->tournament_id,
            'user' => new UserListResource($this->whenLoaded('user')),
            'type' => $this->type,
            'content' => $this->content,
            'meta' => $this->meta,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
            'is_own' => $this->user_id === auth()->id(),
        ];
    }
}
