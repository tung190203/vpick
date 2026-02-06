<?php

namespace App\Services\Club;

use App\Enums\ClubMemberRole;
use App\Enums\ClubMembershipStatus;
use App\Enums\ClubMemberStatus;
use App\Enums\ClubStatus;
use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use App\Models\Club\ClubProfile;
use App\Models\User;
use App\Services\ImageOptimizationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ClubService
{
    public function __construct(
        protected ImageOptimizationService $imageService,
        protected ClubMemberService $memberService
    ) {
    }

    public function createClub(array $data, int $userId): Club
    {
        return DB::transaction(function () use ($data, $userId) {
            // Handle logo upload
            $logoPath = null;
            if (isset($data['logo_url']) && $data['logo_url'] instanceof UploadedFile) {
                $logoPath = $this->imageService->optimize($data['logo_url'], 'logos');
            }

            // Handle cover upload
            $coverPath = null;
            if (isset($data['cover_image_url']) && $data['cover_image_url'] instanceof UploadedFile) {
                $coverPath = $this->imageService->optimize($data['cover_image_url'], 'covers');
            }

            $status = $data['status'] ?? ClubStatus::Active->value;
            $isPublic = $data['is_public'] ?? true;

            // Create club
            $club = Club::create([
                'name' => $data['name'],
                'address' => $data['address'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'logo_url' => $logoPath,
                'status' => $status,
                'is_public' => $isPublic,
                'created_by' => $userId,
            ]);

            // Create profile if cover exists
            if ($coverPath) {
                ClubProfile::create([
                    'club_id' => $club->id,
                    'cover_image_url' => $coverPath,
                ]);
            }

            // Create admin member
            ClubMember::create([
                'club_id' => $club->id,
                'user_id' => $userId,
                'role' => ClubMemberRole::Admin,
                'membership_status' => ClubMembershipStatus::Joined,
                'status' => ClubMemberStatus::Active,
                'joined_at' => now(),
            ]);

            // Load relations
            $club->load([
                'members.user' => function ($query) {
                    $query->with(User::FULL_RELATIONS);
                },
                'profile',
                'creator'
            ]);

            return $club;
        });
    }

    public function updateClub(Club $club, array $data, int $userId): Club
    {
        // Authorization check
        if (!$club->canManage($userId)) {
            throw new \Exception('Chỉ admin/manager mới có quyền cập nhật CLB');
        }

        // QR code validation
        if (isset($data['qr_code_enabled']) && $data['qr_code_enabled']) {
            $hasNewImage = isset($data['qr_code_image_url']) && $data['qr_code_image_url'] instanceof UploadedFile;
            $hasExistingImage = $club->profile && $club->profile->qr_code_image_url;

            if (!$hasNewImage && !$hasExistingImage) {
                throw new \Exception('Vui lòng tải lên ảnh QR code khi bật tính năng này');
            }
        }

        return DB::transaction(function () use ($club, $data) {
            // Handle logo upload
            $logoPath = $club->getRawOriginal('logo_url');
            if (isset($data['logo_url']) && $data['logo_url'] instanceof UploadedFile) {
                if ($logoPath) {
                    $this->deleteImages($logoPath);
                }
                $logoPath = $this->imageService->optimize($data['logo_url'], 'logos');
            }

            // Update club
            $club->update([
                'name' => $data['name'] ?? $club->name,
                'address' => $data['address'] ?? $club->address,
                'latitude' => $data['latitude'] ?? $club->latitude,
                'longitude' => $data['longitude'] ?? $club->longitude,
                'logo_url' => $logoPath,
                'status' => $data['status'] ?? $club->status,
                'is_public' => $data['is_public'] ?? $club->is_public,
            ]);

            // Handle profile updates
            $profile = $club->profile;

            // Handle cover image upload
            if (isset($data['cover_image_url']) && $data['cover_image_url'] instanceof UploadedFile) {
                if ($profile && $profile->getRawCoverImagePath()) {
                    $this->deleteImages($profile->getRawCoverImagePath());
                }
                $coverPath = $this->imageService->optimize($data['cover_image_url'], 'covers');

                if ($profile) {
                    $profile->update(['cover_image_url' => $coverPath]);
                } else {
                    $profile = ClubProfile::create([
                        'club_id' => $club->id,
                        'cover_image_url' => $coverPath,
                    ]);
                }
            }

            // Handle QR code image upload
            if (isset($data['qr_code_image_url']) && $data['qr_code_image_url'] instanceof UploadedFile) {
                if (!$profile) {
                    $profile = $club->profile;
                }

                if ($profile && $profile->getRawQrCodeImagePath()) {
                    $this->deleteImages($profile->getRawQrCodeImagePath());
                }

                $qrCodePath = $this->imageService->optimizeThumbnail($data['qr_code_image_url'], 'qr_codes', 90);

                if ($profile) {
                    $profile->update(['qr_code_image_url' => $qrCodePath]);
                } else {
                    $profile = ClubProfile::create([
                        'club_id' => $club->id,
                        'qr_code_image_url' => $qrCodePath,
                    ]);
                }
            }

            // Handle profile field updates
            $profileFields = ['description', 'phone', 'email', 'website', 'city', 'province', 'country', 'zalo_link', 'zalo_enabled', 'qr_code_enabled'];
            if (collect($profileFields)->some(fn($field) => isset($data[$field]))) {
                if (!$profile) {
                    $profile = $club->profile;
                }

                // Prepare social_links and settings updates
                $socialLinks = $profile && is_array($profile->social_links) ? $profile->social_links : [];
                $settings = $profile && is_array($profile->settings) ? $profile->settings : [];

                // Update Zalo link in social_links
                if (isset($data['zalo_link'])) {
                    if ($data['zalo_link']) {
                        $socialLinks['zalo'] = $data['zalo_link'];
                    } else {
                        unset($socialLinks['zalo']);
                    }
                }

                // Update Zalo enabled in settings
                if (isset($data['zalo_enabled'])) {
                    $settings['zalo_enabled'] = $data['zalo_enabled'];
                }

                // Update QR code enabled in settings
                if (isset($data['qr_code_enabled'])) {
                    $settings['qr_code_enabled'] = $data['qr_code_enabled'];
                }

                // Ensure empty arrays are cast to objects in JSON
                if (empty($socialLinks)) {
                    $socialLinks = (object) [];
                }
                if (empty($settings)) {
                    $settings = (object) [];
                }

                if ($profile) {
                    $profile->update([
                        'description' => $data['description'] ?? $profile->description,
                        'phone' => $data['phone'] ?? $profile->phone,
                        'email' => $data['email'] ?? $profile->email,
                        'website' => $data['website'] ?? $profile->website,
                        'city' => $data['city'] ?? $profile->city,
                        'province' => $data['province'] ?? $profile->province,
                        'country' => $data['country'] ?? $profile->country,
                        'social_links' => $socialLinks,
                        'settings' => $settings,
                    ]);
                } else {
                    ClubProfile::create([
                        'club_id' => $club->id,
                        'description' => $data['description'] ?? null,
                        'phone' => $data['phone'] ?? null,
                        'email' => $data['email'] ?? null,
                        'website' => $data['website'] ?? null,
                        'city' => $data['city'] ?? null,
                        'province' => $data['province'] ?? null,
                        'country' => $data['country'] ?? null,
                        'social_links' => $socialLinks,
                        'settings' => $settings,
                    ]);
                }
            }

            // Refresh and load relations
            $club->refresh()->load([
                'members.user' => function ($query) {
                    $query->with(User::FULL_RELATIONS);
                },
                'profile',
                'creator'
            ]);

            return $club;
        });
    }

    public function deleteClub(Club $club, int $userId): void
    {
        // Authorization check
        if (!$club->canManage($userId)) {
            throw new \Exception('Chỉ admin/manager mới có quyền xóa CLB');
        }

        DB::transaction(function () use ($club) {
            // Delete logo
            $logoPath = $club->getRawOriginal('logo_url');
            if ($logoPath) {
                $this->deleteImages($logoPath);
            }

            // Delete cover
            if ($club->profile) {
                $coverPath = $club->profile->getRawCoverImagePath();
                if ($coverPath) {
                    $this->deleteImages($coverPath);
                }
            }

            // Soft delete club
            $club->delete();
        });
    }

    public function restoreClub(Club $club, int $userId): Club
    {
        $isCreator = $club->created_by === $userId;
        $isSystemAdmin = User::isAdmin($userId);

        if (!$isCreator && !$isSystemAdmin) {
            throw new \Exception('Chỉ người tạo CLB hoặc admin hệ thống mới có quyền khôi phục CLB');
        }

        $club->restore();
        $club->refresh()->load([
            'members.user' => function ($query) {
                $query->with(User::FULL_RELATIONS);
            },
            'profile',
            'creator'
        ]);

        return $club;
    }

    public function getClubDetail(Club $club, ?int $userId): Club
    {
        // Authorization check for private clubs
        if (!$club->is_public) {
            if (!$userId) {
                throw new \Exception('CLB này là riêng tư. Bạn cần đăng nhập để xem');
            }

            $isCreator = $club->created_by === $userId;
            $isMember = $club->members()
                ->where('user_id', $userId)
                ->where('membership_status', ClubMembershipStatus::Joined)
                ->where('status', ClubMemberStatus::Active)
                ->exists();

            if (!$isCreator && !$isMember) {
                throw new \Exception('Bạn không có quyền xem CLB riêng tư này');
            }
        }

        // Get members with ranking
        $members = $club->joinedMembers()->with(['user' => User::FULL_RELATIONS])->get();
        $enrichedMembers = $this->memberService->enrichMembersWithRanking($members);

        // Set the enriched members relation
        $club->setRelation('members', $enrichedMembers);

        return $club;
    }

    public function searchClubs(array $filters, ?int $userId): LengthAwarePaginator
    {
        $query = Club::withFullRelations()->orderBy('created_at', 'desc');

        // Filter private clubs: Only show public clubs OR clubs where user is member/creator
        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->where('is_public', true)
                    ->orWhere('created_by', $userId)
                    ->orWhereHas('members', function ($memberQuery) use ($userId) {
                        $memberQuery->where('user_id', $userId)
                            ->where('membership_status', ClubMembershipStatus::Joined)
                            ->where('status', ClubMemberStatus::Active);
                    });
            });
        } else {
            $query->where('is_public', true);
        }

        // Apply search filters
        if (!empty($filters['name'])) {
            $query->search(['name'], $filters['name']);
        }

        if (!empty($filters['address'])) {
            $query->search(['address'], $filters['address']);
        }

        // Check if there are any text filters
        $hasFilter = !empty($filters['name']) || !empty($filters['address']);

        // Apply bounds filter (only if no text search)
        if (
            !$hasFilter &&
            (!empty($filters['minLat']) ||
                !empty($filters['maxLat']) ||
                !empty($filters['minLng']) ||
                !empty($filters['maxLng']))
        ) {
            $query->inBounds(
                $filters['minLat'],
                $filters['maxLat'],
                $filters['minLng'],
                $filters['maxLng']
            );
        }

        // Apply distance ordering
        if (!empty($filters['lat']) && !empty($filters['lng'])) {
            $query->orderByDistance($filters['lat'], $filters['lng']);
        }

        // Apply radius filter
        if (!empty($filters['lat']) && !empty($filters['lng']) && !empty($filters['radius'])) {
            $query->nearBy($filters['lat'], $filters['lng'], $filters['radius']);
        }

        $perPage = $filters['perPage'] ?? Club::PER_PAGE;
        return $query->paginate($perPage);
    }

    public function leaveClub(Club $club, int $userId, ?int $transferToUserId = null): array
    {
        $member = $club->activeMembers()->where('user_id', $userId)->first();

        if (!$member) {
            throw new \Exception('Bạn không phải thành viên active của CLB này');
        }

        // Check if admin is the only admin
        if ($member->role === ClubMemberRole::Admin) {
            $adminCount = $this->memberService->countActiveAdmins($club);

            if ($adminCount === 1) {
                // Admin only, must transfer ownership
                if (!$transferToUserId) {
                    throw new \Exception('Bạn là admin duy nhất của CLB. Vui lòng nhượng lại quyền quản lý cho thành viên khác trước khi rời.');
                }

                $newAdmin = $club->activeMembers()
                    ->where('user_id', $transferToUserId)
                    ->where('id', '!=', $member->id)
                    ->with('user')
                    ->first();

                if (!$newAdmin) {
                    throw new \Exception('Người được nhượng quyền phải là thành viên active của CLB và không phải chính bạn');
                }

                return DB::transaction(function () use ($member, $newAdmin) {
                    // Promote member to admin
                    $newAdmin->update([
                        'role' => ClubMemberRole::Admin,
                    ]);

                    // Current admin leaves club
                    $member->update([
                        'membership_status' => ClubMembershipStatus::Left,
                        'status' => ClubMemberStatus::Inactive,
                        'left_at' => now(),
                    ]);

                    return [
                        'transferred_to' => [
                            'user_id' => $newAdmin->user_id,
                            'user_name' => $newAdmin->user->full_name ?? 'N/A',
                        ],
                    ];
                });
            }
            // Multiple admins, can leave normally
        }

        // Regular member or admin with other admins → leave normally
        $member->update([
            'membership_status' => ClubMembershipStatus::Left,
            'status' => ClubMemberStatus::Inactive,
            'left_at' => now(),
        ]);

        return [];
    }

    public function verifyClub(Club $club, bool $isVerified): Club
    {
        $club->update(['is_verified' => $isVerified]);
        return $club->refresh();
    }

    public function updateFund(Club $club, string $qrCodeUrl, int $userId): array
    {
        if (!$club->canManageFinance($userId)) {
            throw new \Exception('Chỉ admin/manager/treasurer mới có quyền cập nhật quỹ');
        }

        $mainWallet = $club->mainWallet;
        if (!$mainWallet) {
            throw new \Exception('CLB chưa có ví chính');
        }

        $mainWallet->update(['qr_code_url' => $qrCodeUrl]);

        return [
            'club_id' => $club->id,
            'main_wallet_id' => $mainWallet->id,
            'qr_code_url' => $mainWallet->qr_code_url,
        ];
    }

    private function deleteImages(string ...$paths): void
    {
        foreach ($paths as $path) {
            if ($path) {
                $this->imageService->deleteOldImage($path);
            }
        }
    }
}
