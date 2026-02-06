<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class ClubActivityParticipantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_activity_id' => $this->club_activity_id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'has_checked_in' => $this->checked_in_at !== null,
            'has_paid' => $this->wallet_transaction_id !== null,
            'user' => new UserResource($this->whenLoaded('user')),
            'sport_score' => $this->sport_score ? (float) $this->sport_score : null,
            'vndupr_score' => $this->vndupr_score ? (float) $this->vndupr_score : null,
            'checked_in_at' => $this->checked_in_at?->toISOString(),
            'joined_at' => $this->created_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
