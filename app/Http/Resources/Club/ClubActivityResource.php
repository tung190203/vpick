<?php

namespace App\Http\Resources\Club;

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
            'is_recurring' => $this->is_recurring,
            'recurring_schedule' => $this->recurring_schedule,
            'start_time' => $this->start_time?->toISOString(),
            'end_time' => $this->end_time?->toISOString(),
            'cancellation_deadline' => $this->cancellation_deadline?->toISOString(),
            'location' => $this->location,
            'venue_address' => $this->venue_address,
            'reminder_minutes' => $this->reminder_minutes,
            'fee_amount' => $this->fee_amount !== null ? (float) $this->fee_amount : null,
            'guest_fee' => $this->guest_fee !== null ? (float) $this->guest_fee : null,
            'penalty_percentage' => $this->penalty_percentage !== null ? (float) $this->penalty_percentage : null,
            'fee_split_type' => $this->fee_split_type ?? 'fixed',
            'allow_member_invite' => (bool) ($this->allow_member_invite ?? false),
            'max_participants' => $this->max_participants !== null ? (int) $this->max_participants : null,
            'collected_amount' => (float) ($this->collected_amount ?? 0),
            'qr_code_url' => $this->qr_code_url,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'participants_count' => $this->whenLoaded('participants', fn() => $this->participants->count()),
            'accepted_count' => $this->whenLoaded('participants', fn() => $this->participants->where('status', 'accepted')->count()),
            'participants' => ClubActivityParticipantResource::collection($this->whenLoaded('participants')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'mini_tournament' => $this->whenLoaded('miniTournament'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
