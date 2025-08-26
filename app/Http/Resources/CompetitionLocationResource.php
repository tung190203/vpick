<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompetitionLocationResource extends JsonResource
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
            'location' => new LocationResource($this->whenLoaded('location')),
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
