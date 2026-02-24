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

    public function toArray(Request $request): array
    {
        $isMember = auth()->check() && ClubMember::where('club_id', $this->id)
            ->where('user_id', auth()->id())
            ->where('membership_status', ClubMembershipStatus::Joined)
            ->where('status', ClubMemberStatus::Active)
            ->exists();

        if (!$isMember) {
            return $this->toLimitedArray();
        }

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
            'rank' => $this->rank ?? null,
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
            'is_member' => true,
            'has_pending_request' => $this->when(auth()->check(), fn () =>
                ClubMember::where('club_id', $this->id)
                    ->where('user_id', auth()->id())
                    ->where('membership_status', ClubMembershipStatus::Pending)
                    ->whereNull('invited_by')
                    ->exists(),
                false
            ),
            'has_invitation' => $this->when(auth()->check(), fn () =>
                ClubMember::where('club_id', $this->id)
                    ->where('user_id', auth()->id())
                    ->where('membership_status', ClubMembershipStatus::Pending)
                    ->whereNotNull('invited_by')
                    ->exists(),
                false
            ),
            'can_edit_footer' => $this->when(auth()->check(), fn () => $this->resource->canEditFooter(auth()->id()), false),
            'profile' => $this->whenLoaded('profile', fn () => static::formatProfile($this->profile), static::getDefaultProfile()),
            'wallets' => $this->whenLoaded('wallets'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /** Chỉ trả về thông tin chung + giới thiệu + footer khi chưa tham gia CLB */
    protected function toLimitedArray(): array
    {
        $profile = $this->profile;

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
            'is_member' => false,
            'profile' => [
                'id' => $profile?->id,
                'description' => $profile?->description,
                'cover_image_url' => $profile?->cover_image_url,
                'footer' => $profile?->footer,
            ],
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
            'footer' => $profile->footer,
            'latitude' => $profile->latitude,
            'longitude' => $profile->longitude,
            'zalo_link' => $profile->zalo_link,
            'zalo_link_enabled' => (bool) ($settings['zalo_link_enabled'] ?? false),
            'qr_zalo' => $profile->qr_zalo_url,
            'qr_zalo_enabled' => (bool) ($settings['qr_zalo_enabled'] ?? false),
        ];
    }

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
            'footer' => null,
            'latitude' => null,
            'longitude' => null,
            'zalo_link' => null,
            'zalo_link_enabled' => false,
            'qr_zalo' => null,
            'qr_zalo_enabled' => false,
        ];
    }

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
