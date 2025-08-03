<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TournamentResource extends JsonResource
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
            'name' => $this->name,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'location' => $this->location,
            'club' => $this->whenLoaded('club', function () {
                return [
                    'id' => $this->club->id,
                    'name' => $this->club->name,
                ];
            }),
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->full_name,
                ];
            }),
            'type' => $this->type,
            'level' => $this->level,
            'description' => $this->description,
            'status' => match (true) {
                !$this->start_date || !$this->end_date => null,
                now()->between($this->start_date, $this->end_date) => 'ongoing',
                now()->lt($this->start_date) => 'upcoming',
                default => 'finished',
            },
            'joined' => $this->whenLoaded('participants', function () use ($request) {
                $user = $request->user();
                if (!$user) return false;

                return $this->participants->contains(function ($participant) use ($user) {
                    return $participant->user_id === $user->id;
                });
            }),
            'matches_count' => $this->whenLoaded('matches', function () {
                return $this->matches->count();
            }),
            ''
        ];
    }
}
