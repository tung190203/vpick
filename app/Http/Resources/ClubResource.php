<?php

namespace App\Http\Resources;

use App\Enums\ClubMemberStatus;
use App\Enums\ClubMembershipStatus;
use App\Http\Resources\Club\ClubMemberResource;
use App\Models\Club\ClubMember;
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
            'rank' => $this->when(isset($this->rank), $this->rank),
            'created_by' => $this->created_by,
            'members' => ClubMemberResource::collection($this->whenLoaded('activeMembers', $this->whenLoaded('members', collect()))),
            'quantity_members' => $this->when(isset($this->active_members_count), fn () => $this->active_members_count,
                $this->whenLoaded('members', fn () =>
                    $this->members
                        ->filter(fn ($m) => $m->user !== null)
                        ->where('membership_status', ClubMembershipStatus::Joined)
                        ->where('status', ClubMemberStatus::Active)
                        ->count(),
                0
            )),
            'skill_level' => $this->whenLoaded('activeMembers', fn () => $this->computeSkillLevel($this->activeMembers))
                ?? $this->whenLoaded('members', fn () => $this->computeSkillLevel(
                    $this->members->filter(fn ($m) => $m->user !== null)
                        ->where('membership_status', ClubMembershipStatus::Joined)
                        ->where('status', ClubMemberStatus::Active)
                )),
            'is_member' => $this->when(auth()->check(), fn () =>
                array_key_exists('_is_member', $this->resource->getAttributes()) ? (bool) $this->_is_member : ClubMember::where('club_id', $this->id)
                    ->where('user_id', auth()->id())
                    ->where('membership_status', ClubMembershipStatus::Joined)
                    ->where('status', ClubMemberStatus::Active)
                    ->exists(),
                false
            ),
            'has_pending_request' => $this->when(auth()->check(), fn () =>
                array_key_exists('_has_pending_request', $this->resource->getAttributes()) ? (bool) $this->_has_pending_request : ClubMember::where('club_id', $this->id)
                    ->where('user_id', auth()->id())
                    ->where('membership_status', ClubMembershipStatus::Pending)
                    ->whereNull('invited_by')
                    ->exists(),
                false
            ),
            'has_invitation' => $this->when(auth()->check(), fn () =>
                array_key_exists('_has_invitation', $this->resource->getAttributes()) ? (bool) $this->_has_invitation : ClubMember::where('club_id', $this->id)
                    ->where('user_id', auth()->id())
                    ->where('membership_status', ClubMembershipStatus::Pending)
                    ->whereNotNull('invited_by')
                    ->exists(),
                false
            ),
            'profile' => $this->whenLoaded('profile', fn () => static::formatProfile($this->profile), static::getDefaultProfile()),
            'wallets' => $this->whenLoaded('wallets'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    public static function formatProfile(?\Illuminate\Database\Eloquent\Model $profile): array
    {
        if (!$profile) {
            return static::getDefaultProfile();
        }

        $settings = $profile->settings ?? [];

        return [
            'id' => $profile->id,
            'description' => $profile->description,
            'cover_image_url' => $profile->cover_image_url,
            'qr_code_image_url' => $profile->qr_code_image_url,
            'qr_code_enabled' => (bool) ($settings['qr_code_enabled'] ?? false),
            'phone' => $profile->phone,
            'email' => $profile->email,
            'website' => $profile->website,
            'address' => $profile->address,
            'city' => $profile->city,
            'province' => $profile->province,
            'country' => $profile->country,
            'latitude' => $profile->latitude,
            'longitude' => $profile->longitude,
            'zalo_link' => $profile->zalo_link,
            'zalo_link_enabled' => (bool) ($settings['zalo_link_enabled'] ?? false),
            'qr_zalo' => $profile->qr_zalo_url,
            'qr_zalo_enabled' => (bool) ($settings['qr_zalo_enabled'] ?? false),
        ];
    }

    /**
     * Cấu trúc profile mặc định khi CLB chưa có profile (tránh null cho frontend).
     */
    protected static function getDefaultProfile(): array
    {
        return [
            'id' => null,
            'description' => null,
            'cover_image_url' => null,
            'qr_code_image_url' => null,
            'qr_code_enabled' => false,
            'phone' => null,
            'email' => null,
            'website' => null,
            'address' => null,
            'city' => null,
            'province' => null,
            'country' => null,
            'latitude' => null,
            'longitude' => null,
            'zalo_link' => null,
            'zalo_link_enabled' => false,
            'qr_zalo' => null,
            'qr_zalo_enabled' => false,
        ];
    }

    /**
     * Tính skill_level từ collection members
     */
    protected function computeSkillLevel($members): ?array
    {
        $scores = $members->map(fn ($member) => $this->getMemberVnduprScore($member))->filter(fn ($s) => $s !== null);
        return $scores->isEmpty() ? null : [
            'min' => round($scores->min(), 1),
            'max' => round($scores->max(), 1),
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
