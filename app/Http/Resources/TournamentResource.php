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
            'poster' => $this->poster_url,
            'sport_id' => $this->sport_id,
            'sport' => $this->whenLoaded('sport', function () {
                return [
                    'id' => $this->sport->id,
                    'name' => $this->sport->name,
                    'icon' => $this->sport->icon,
                ];
            }),
            'name' => $this->name,
            'location' => $this->location,
            'start_date' => $this->start_date,
            // 'end_date' => $this->end_date,
            'registration_open_at' => $this->registration_open_at,
            'registration_closed_at' => $this->registration_closed_at,
            'early_registration_deadline' => $this->early_registration_deadline,
            'duration' => $this->duration,
            'enable_dupr' => $this->enable_dupr,
            'enable_vndupr' => $this->enable_vndupr,
            'min_level' => $this->min_level,
            'max_level' => $this->max_level,
            'age_group' => $this->age_group,
            'age_group_text' => $this->age_group_text,
            'gender_policy' => $this->gender_policy,
            'gender_policy_text' => $this->gender_policy_text,
            'participant' => $this->participant,
            'max_team' => $this->max_team,
            'min_player_per_team' => $this->min_player_per_team,
            'max_player_per_team' => $this->max_player_per_team,
            'max_player' => $this->max_player,
            'fee' => $this->fee,
            'standard_fee_amount' => $this->standard_fee_amount,
            'is_private' => $this->is_private,
            'auto_approve' => $this->auto_approve,
            'status_text' => $this->status_text,
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
            'tournament_staff' => TournamentStaffResource::collection($this->whenLoaded('tournamentStaffs')),
            'tournamnet_participants' => $this->whenLoaded('participants', function() {
                return $this->participants->map(function($participant) {
                    return [
                        'id' => $participant->id,
                        'type' => $participant->type,
                        'user' => $participant->type === 'user' && $participant->user ? [
                            'id' => $participant->user->id,
                            'name' => $participant->user->full_name,
                        ] : null,
                        'team' => $participant->type === 'team' && $participant->team ? [
                            'id' => $participant->team->id,
                            'name' => $participant->team->name,
                            'members' => $participant->team->members->map(fn($m) => [
                                'id' => $m->id,
                                'name' => $m->full_name,
                            ]),
                        ] : null,
                        'registered_at' => $participant->created_at,
                    ];
                });
            })
            // 'tournament_types' => $this->whenLoaded('tournamentTypes', function () {
            //     return $this->tournamentTypes->map(function ($type) {
            //         return [
            //             'id' => $type->id,
            //             'type' => $type->type,
            //             'description' => $type->description,
            //             'groups' => $type->groups->map(function ($group) {
            //                 return [
            //                     'id' => $group->id,
            //                     'name' => $group->name,
            //                     'matches' => $group->matches->map(function ($match) {
            //                         $getParticipantData = function ($participant) {
            //                             if (!$participant) {
            //                                 return null;
            //                             }

            //                             if ($participant->type === 'user' && $participant->user) {
            //                                 return [
            //                                     'id' => $participant->id,
            //                                     'type' => 'user',
            //                                     'user' => [
            //                                         'id' => $participant->user->id,
            //                                         'name' => $participant->user->full_name,
            //                                     ]
            //                                 ];
            //                             }

            //                             if ($participant->type === 'team' && $participant->team) {
            //                                 return [
            //                                     'id' => $participant->id,
            //                                     'type' => 'team',
            //                                     'team' => [
            //                                         'id' => $participant->team->id,
            //                                         'name' => $participant->team->name,
            //                                         'members' => $participant->team->members->map(fn($m) => [
            //                                             'id' => $m->id,
            //                                             'name' => $m->full_name,
            //                                         ]),
            //                                     ]
            //                                 ];
            //                             }                                        

            //                             return null;
            //                         };

            //                         return [
            //                             'id' => $match->id,
            //                             'group_id' => $match->group_id ?? null,
            //                             'round' => $match->round ?? null,
            //                             'participant1' => $getParticipantData($match->participant1),
            //                             'participant2' => $getParticipantData($match->participant2),
            //                             'referee_id' => $match->referee_id,
            //                             'status' => $match->status,
            //                             'scheduled_at' => $match->scheduled_at,
            //                         ];
            //                     }),

            //                 ];
            //             }),
            //         ];
            //     });
            // }),
        ];
    }
}
