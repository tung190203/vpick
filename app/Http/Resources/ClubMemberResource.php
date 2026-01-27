<?php

namespace App\Http\Resources;

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
            'role' => $this->role,
            'position' => $this->position,
            'status' => $this->status,
            'message' => $this->when($this->status === 'pending', $this->message),
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'rejection_reason' => $this->when($this->status === 'inactive' && $this->rejection_reason, $this->rejection_reason),
            'joined_at' => $this->joined_at?->toISOString(),
            'left_at' => $this->left_at?->toISOString(),
            'notes' => $this->notes,
            'user' => new UserListResource($this->whenLoaded('user')),
            'reviewer' => new UserListResource($this->whenLoaded('reviewer')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
