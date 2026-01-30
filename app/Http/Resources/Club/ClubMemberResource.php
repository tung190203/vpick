<?php

namespace App\Http\Resources\Club;

use App\Http\Resources\ListClubResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'user_id' => $this->user_id,
            'invited_by' => $this->when(isset($this->invited_by), $this->invited_by),
            'role' => $this->role,
            'position' => $this->position,
            'membership_status' => $this->membership_status,
            'status' => $this->status,
            'message' => $this->when($this->membership_status?->value === 'pending', $this->message),
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'rejection_reason' => $this->when(isset($this->rejection_reason), $this->rejection_reason),
            'joined_at' => $this->joined_at?->toISOString(),
            'left_at' => $this->left_at?->toISOString(),
            'notes' => $this->notes,
            'rank_in_club' => $this->when(isset($this->rank_in_club), $this->rank_in_club),
            'user' => new UserResource($this->whenLoaded('user')),
            'club' => $this->whenLoaded('club', fn () => new ListClubResource($this->club)),
            'inviter' => new UserResource($this->whenLoaded('inviter')),
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
