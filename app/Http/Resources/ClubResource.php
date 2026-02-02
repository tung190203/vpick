<?php

namespace App\Http\Resources;

use App\Enums\ClubMemberStatus;
use App\Enums\ClubMembershipStatus;
use App\Http\Resources\Club\ClubMemberResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'logo_url' => $this->logo_url,
            'status' => $this->status,
            'is_public' => (bool) ($this->is_public ?? true),
            'is_verified' => (bool) $this->is_verified,
            'created_by' => $this->created_by,
            'members' => ClubMemberResource::collection($this->whenLoaded('members')),
            'quantity_members' => $this->whenLoaded('members', fn() =>
                $this->members
                    ->filter(fn($m) => $m->user !== null) // Chỉ đếm members có user tồn tại
                    ->where('membership_status', ClubMembershipStatus::Joined)
                    ->where('status', ClubMemberStatus::Active)
                    ->count(),
                0
            ),
            'skill_level' => $this->whenLoaded('members', function () {
                $scores = $this->members
                    ->filter(fn($m) => $m->user !== null) // Chỉ lấy members có user tồn tại
                    ->where('membership_status', ClubMembershipStatus::Joined)
                    ->where('status', ClubMemberStatus::Active)
                    ->map(fn($member) => $this->getMemberVnduprScore($member))
                    ->filter(fn($score) => $score !== null);

                if ($scores->isEmpty()) {
                    return null;
                }

                return [
                    'min' => round($scores->min(), 1),
                    'max' => round($scores->max(), 1),
                ];
            }, null),
            'is_member' => $this->whenLoaded('members', fn () => $this->members->contains(fn ($m) => $m->user_id === auth()->id() && $m->membership_status === ClubMembershipStatus::Joined && $m->status === ClubMemberStatus::Active), false),
            'has_pending_request' => $this->when(auth()->check(), fn () => $this->resource->members()->where('user_id', auth()->id())->where('membership_status', ClubMembershipStatus::Pending)->exists(), false),
            'profile' => $this->whenLoaded('profile', function () {
                return $this->profile ? [
                    'id' => $this->profile->id,
                    'description' => $this->profile->description,
                    'cover_image_url' => $this->profile->cover_image_url,
                    'phone' => $this->profile->phone,
                    'email' => $this->profile->email,
                    'website' => $this->profile->website,
                    'address' => $this->profile->address,
                    'city' => $this->profile->city,
                    'province' => $this->profile->province,
                    'country' => $this->profile->country,
                    'latitude' => $this->profile->latitude,
                    'longitude' => $this->profile->longitude,
                    'social_links' => $this->profile->social_links,
                    'settings' => $this->profile->settings,
                ] : null;
            }),
            'wallets' => $this->whenLoaded('wallets'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Lấy điểm vndupr của member (từ user.vnduprScores hoặc user.sports.scores)
     */
    protected function getMemberVnduprScore($member): ?float
    {
        $user = $member->user ?? null;
        if (!$user) {
            return null;
        }
        $score = null;
        if ($user->relationLoaded('vnduprScores')) {
            $max = $user->vnduprScores->max('score_value');
            $score = $max !== null ? (float) $max : null;
        }
        if ($score === null && $user->relationLoaded('sports')) {
            foreach ($user->sports ?? [] as $userSport) {
                $scores = $userSport->relationLoaded('scores') ? $userSport->scores : collect();
                $vndupr = $scores->where('score_type', 'vndupr_score')->sortByDesc('created_at')->first();
                if ($vndupr) {
                    $score = (float) $vndupr->score_value;
                    break;
                }
            }
        }
        return $score;
    }
}
