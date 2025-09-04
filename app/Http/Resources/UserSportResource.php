<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'sport_id' => $this->sport_id,
            'sport_name' => $this->when($this->relationLoaded('sport') && $this->sport, $this->sport->name),
            'scores' => UserSportScoreResource::collection($this->whenLoaded('scores')),
        ];
    }
}
