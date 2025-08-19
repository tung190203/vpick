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
            'status' => $this->status,
            'location' => $this->location,
            'level' => $this->level,
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
            'tournament_types' => $this->whenLoaded('tournamentTypes', function () {
                return $this->tournamentTypes->map(function ($type) {
                    return [
                        'id' => $type->id,
                        'type' => $type->type,
                        'description' => $type->description,
                        'groups' => $type->groups->map(function ($group) {
                            return [
                                'id' => $group->id,
                                'name' => $group->name,
                                'matches' => $group->matches->map(function ($match) {
                                    $getParticipantData = function ($participant) {
                                        if (!$participant) {
                                            return null;
                                        }

                                        if ($participant->type === 'user' && $participant->user) {
                                            return [
                                                'id' => $participant->id,
                                                'type' => 'user',
                                                'user' => [
                                                    'id' => $participant->user->id,
                                                    'name' => $participant->user->full_name,
                                                ]
                                            ];
                                        }

                                        if ($participant->type === 'team' && $participant->team) {
                                            return [
                                                'id' => $participant->id,
                                                'type' => 'team',
                                                'team' => [
                                                    'id' => $participant->team->id,
                                                    'name' => $participant->team->name,
                                                    'members' => $participant->team->members->map(fn($m) => [
                                                        'id' => $m->id,
                                                        'name' => $m->full_name,
                                                    ]),
                                                ]
                                            ];
                                        }                                        

                                        return null;
                                    };

                                    return [
                                        'id' => $match->id,
                                        'group_id' => $match->group_id ?? null,
                                        'round' => $match->round ?? null,
                                        'participant1' => $getParticipantData($match->participant1),
                                        'participant2' => $getParticipantData($match->participant2),
                                        'referee_id' => $match->referee_id,
                                        'status' => $match->status,
                                        'scheduled_at' => $match->scheduled_at,
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
