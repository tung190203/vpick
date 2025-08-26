<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MiniTournamentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $participants = $this->relationLoaded('participants') ? $this->participants : collect();

        return [
            'id' => $this->id,
            'poster' => $this->poster,
            'sport' => new SportResource($this->whenLoaded('sport')),
            'created_by' => new UserListResource($this->whenLoaded('creator')),
            'name' => $this->name,
            'description' => $this->description,
            'match_type' => $this->match_type,
            'match_type_text' => $this->match_type_text,
            'starts_at' => $this->starts_at,
            'duration_minutes' => $this->duration_minutes,
            'competition_location' => new CompetitionLocationResource($this->whenLoaded('competitionLocation')),
            'is_private' => $this->is_private,
            'fee_amount' => $this->fee_amount,
            'max_players' => $this->max_players,
            'enable_dupr' => $this->enable_dupr,
            'enable_vndupr' => $this->enable_vndupr,
            'min_rating' => $this->min_rating,
            'max_rating' => $this->max_rating,
            'gender_policy' => $this->gender_policy,
            'gender_policy_text' => $this->gender_policy_text,
            'min_age' => $this->min_age,
            'max_age' => $this->max_age,
            'repeat_type' => $this->repeat_type,
            'repeat_type_text' => $this->repeat_type_text,
            'role_type' => $this->role_type,
            'role_type_text' => $this->role_type_text,
            'lock_cancellation' => $this->lock_cancellation,
            'auto_approve' => $this->auto_approve,
            'allow_participant_add_friends' => $this->allow_participant_add_friends,
            'send_notification' => $this->send_notification,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'participants' => [
                'users' => MiniParticipantResource::collection(
                    $participants->where('type', 'user')
                ),
                'teams' => MiniParticipantResource::collection(
                    $participants->where('type', 'team')
                ),
            ],
            'all_users' => UserListResource::collection($this->all_users ?? collect()),
        ];
    }
}
