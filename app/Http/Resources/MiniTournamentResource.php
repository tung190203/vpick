<?php

namespace App\Http\Resources;

use App\Models\MiniTournamentStaff;
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
            'name' => $this->name,
            'description' => $this->description,
            'play_mode' => $this->play_mode,
            'play_mode_text' => $this->play_mode_text,
            'format' => $this->format,
            'format_text' => $this->format_text,

            // Updated time fields
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration' => $this->duration,

            'competition_location' => new CompetitionLocationResource($this->whenLoaded('competitionLocation')),
            'is_private' => $this->is_private,

            // Updated fee fields
            'has_fee' => $this->has_fee,
            'auto_split_fee' => $this->auto_split_fee,
            'fee_amount' => $this->fee_amount,
            'fee_description' => $this->fee_description,
            'qr_code_url' => $this->qr_code_url,
            'payment_account_id' => $this->payment_account_id,
            // Computed fee properties
            'fee_per_person' => $this->fee_per_person,
            'total_fee_expected' => $this->total_fee_expected,

            'max_players' => $this->max_players,

            // Rating
            'min_rating' => $this->min_rating,
            'max_rating' => $this->max_rating,

            // Game rules
            'set_number' => $this->set_number,
            'base_points' => $this->base_points,
            'points_difference' => $this->points_difference,
            'max_points' => $this->max_points,

            // Gender (replaced gender_policy)
            'gender' => $this->gender,
            'gender_text' => $this->gender_text,

            // Updated new fields
            'apply_rule' => $this->apply_rule,
            'allow_cancellation' => $this->allow_cancellation,
            'cancellation_duration' => $this->cancellation_duration,
            'auto_approve' => $this->auto_approve,
            'allow_participant_add_friends' => $this->allow_participant_add_friends,

            'status' => $this->status,
            'status_text' => $this->status_text,

            'staff' => $this->whenLoaded('staff', function () {
                return $this->staff
                    ->groupBy(fn($staff) => MiniTournamentStaff::getRoleText( $staff->pivot->role))
                    ->map(fn($group) => MiniTournamentStaffResource::collection($group));
            }),
            'participants' => MiniParticipantResource::collection($participants),
            'matches' => $this->whenLoaded('matches', function () {
                return MiniMatchResource::collection($this->matches);
            }),
            'all_users' => UserListResource::collection($this->all_users ?? collect()),

            // Recurring schedule
            // Same format as clubs: { period, week_days, recurring_date }
            'recurring_schedule' => $this->recurring_schedule,
        ];
    }
}
