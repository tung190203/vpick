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
            'types' => $this->whenLoaded('types', function () {
                return $this->types->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'type' => $type->type,
                        'description' => $type->description,
                        'groups' => $type->groups->map(function ($group) {
                            return [
                                'id' => $group->id,
                                'name' => $group->name,
                                'matches' => $group->matches->map(function ($match) {
                                    return [
                                        'id' => $match->id,
                                        'player1' => $match->player1 ? [
                                            'id' => $match->player1->id,
                                            'name' => $match->player1->full_name,
                                        ] : null,
                                        'player2' => $match->player2 ? [
                                            'id' => $match->player2->id,
                                            'name' => $match->player2->full_name,
                                        ] : null,
                                        'team1' => $match->team1 ? [
                                            'id' => $match->team1->id,
                                            'name' => $match->team1->name,
                                        ] : null,
                                        'team2' => $match->team2 ? [
                                            'id' => $match->team2->id,
                                            'name' => $match->team2->name,
                                        ] : null,
                                        'status' => $match->status,
                                    ];
                                }),
                            ];
                        }),
                    ];
                });
            }),
        ];
    }
}
