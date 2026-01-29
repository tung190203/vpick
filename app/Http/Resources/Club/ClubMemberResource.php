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
            'status' => $this->status,
            'message' => $this->when($this->status === 'pending', $this->message),
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'rejection_reason' => $this->when($this->status === 'inactive' && $this->rejection_reason, $this->rejection_reason),
            'joined_at' => $this->joined_at?->toISOString(),
            'left_at' => $this->left_at?->toISOString(),
            'notes' => $this->notes,
            'vndupr_score' => $this->whenLoaded('user', fn () => $this->getUserVnduprScore()),
            'user' => new UserResource($this->whenLoaded('user')),
            'club' => $this->whenLoaded('club', fn () => new ListClubResource($this->club)),
            'inviter' => new UserResource($this->whenLoaded('inviter')),
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Lấy điểm vndupr của user (từ sports.scores hoặc vnduprScores)
     */
    protected function getUserVnduprScore(): ?float
    {
        $user = $this->user;
        if (!$user) {
            return null;
        }
        $score = null;
        if ($user->relationLoaded('sports')) {
            foreach ($user->sports ?? [] as $userSport) {
                $scores = $userSport->relationLoaded('scores') ? $userSport->scores : collect();
                $vndupr = $scores->where('score_type', 'vndupr_score')->sortByDesc('created_at')->first();
                if ($vndupr) {
                    $score = (float) $vndupr->score_value;
                    break;
                }
            }
        }
        if ($score === null && $user->relationLoaded('vnduprScores')) {
            $max = $user->vnduprScores->max('score_value');
            $score = $max !== null ? (float) $max : null;
        }
        return $score;
    }
}
