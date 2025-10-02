<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListTournamentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return  [
            'id' => $this->id,
            'poster' => $this->poster_url,
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $this->status,
            'location' => $this->location,
            'description' => $this->description,
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->full_name,
                ];
            }),
            'club' => $this->whenLoaded('club', function () {
                return [
                    'id' => $this->club->id,
                    'name' => $this->club->name,
                ];
            }),
        ];
    }
}
