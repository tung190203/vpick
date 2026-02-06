<?php

namespace App\Http\Resources\Club;

use App\Enums\ClubActivityParticipantStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class ClubActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'mini_tournament_id' => $this->mini_tournament_id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'is_recurring' => $this->recurring_schedule !== null,
            'recurring_schedule' => $this->recurring_schedule,
            'start_time' => $this->start_time?->toISOString(),
            'end_time' => $this->end_time?->toISOString(),
            'duration' => $this->duration,
            'cancellation_deadline' => $this->cancellation_deadline?->toISOString(),
            'cancellation_deadline_hours' => $this->cancellation_deadline_hours,
            'location' => $this->address,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'reminder_minutes' => $this->reminder_minutes,
            'fee_amount' => $this->fee_amount !== null ? (float) $this->fee_amount : null,
            'fee_description' => $this->fee_description,
            'guest_fee' => $this->guest_fee !== null ? (float) $this->guest_fee : null,
            'penalty_amount' => $this->penalty_amount !== null ? (float) $this->penalty_amount : null,
            'fee_split_type' => $this->fee_split_type?->value ?? 'fixed',
            'allow_member_invite' => (bool) ($this->allow_member_invite ?? false),
            'is_public' => (bool) ($this->is_public ?? true),
            'max_participants' => $this->max_participants !== null ? (int) $this->max_participants : null,
            'collected_amount' => (float) ($this->collected_amount ?? 0),
            'qr_code_url' => $this->qr_code_url,
            'check_in_url' => $this->check_in_token
                ? url("/api/clubs/{$this->club_id}/activities/{$this->id}/check-in?token={$this->check_in_token}")
                : null,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'participants_count' => $this->whenLoaded('participants', fn() => $this->participants->count()),
            'accepted_count' => $this->whenLoaded('participants', fn() => $this->participants->where('status', ClubActivityParticipantStatus::Accepted)->count()),
            'participants' => ClubActivityParticipantResource::collection($this->whenLoaded('participants')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'mini_tournament' => $this->whenLoaded('miniTournament'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
