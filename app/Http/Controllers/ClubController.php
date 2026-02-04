<?php

namespace App\Http\Controllers;

use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Enums\ClubMembershipStatus;
use App\Enums\ClubStatus;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use App\Models\Club\ClubProfile;
use App\Models\User;
use App\Models\VnduprHistory;
use App\Models\Matches;
use App\Models\MiniMatch;
use App\Http\Resources\ClubResource;
use App\Http\Resources\Club\ClubLeaderboardResource;
use Carbon\Carbon;
use App\Services\GeocodingService;
use App\Services\ImageOptimizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClubController extends Controller
{
    public function __construct(protected ImageOptimizationService $imageService)
    {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'lat' => 'nullable',
            'lng' => 'nullable',
            'radius' => 'nullable|numeric|min:1',
            'minLat' => 'nullable',
            'maxLat' => 'nullable',
            'minLng' => 'nullable',
            'maxLng' => 'nullable',
            'perPage' => 'sometimes|integer|min:1|max:200',
        ]);

        $userId = auth()->id();
        $query = Club::withFullRelations()->orderBy('created_at', 'desc');

            // Filter private clubs: Chỉ hiển thị public clubs HOẶC clubs mà user là member/creator
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

        if (!empty($validated['name'])) {
            $query->search(['name'], $validated['name']);
        }

        if (!empty($validated['address'])) {
            $query->search(['address'], $validated['address']);
        }

        $hasFilter = collect([
            'name',
            'address',
        ])->some(fn($key) => $request->filled($key));

        if (
            !$hasFilter &&
            (!empty($validated['minLat']) ||
                !empty($validated['maxLat']) ||
                !empty($validated['minLng']) ||
                !empty($validated['maxLng']))
        ) {
            $query->inBounds(
                $validated['minLat'],
                $validated['maxLat'],
                $validated['minLng'],
                $validated['maxLng']
            );
        }

        if (!empty($validated['lat']) && !empty($validated['lng'])) {
            $query->orderByDistance($validated['lat'], $validated['lng']);
        }

        if (!empty($validated['lat']) && !empty($validated['lng']) && !empty($validated['radius'])) {
            $query->nearBy($validated['lat'], $validated['lng'], $validated['radius']);
        }

        $perPage = $validated['perPage'] ?? Club::PER_PAGE;
        $clubs = $query->paginate($perPage);

        $data = [
            'clubs' => ClubResource::collection($clubs),
        ];

        $meta = [
            'current_page' => $clubs->currentPage(),
            'last_page'    => $clubs->lastPage(),
            'per_page'     => $clubs->perPage(),
            'total'        => $clubs->total(),
        ];

        return ResponseHelper::success($data, 'Lấy danh sách câu lạc bộ thành công', 200, $meta);
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:clubs,name,NULL,id,deleted_at,NULL',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'logo_url' => 'nullable|image|max:2048',
            'cover_image_url' => 'nullable|image|max:2048',
            'status' => ['nullable', Rule::enum(ClubStatus::class)],
            'is_public' => 'nullable|boolean',
        ]);

        $userId = auth()->id();
        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập để tạo CLB', 401);
        }

        return DB::transaction(function () use ($request, $userId) {
            $logoPath = null;
            if ($request->hasFile('logo_url')) {
                $logoPath = $this->imageService->optimize($request->file('logo_url'), 'logos');
            }

            $coverPath = null;
            if ($request->hasFile('cover_image_url')) {
                $coverPath = $this->imageService->optimize($request->file('cover_image_url'), 'covers');
            }

            $status = $request->input('status', ClubStatus::Active->value);
            $isPublic = $request->boolean('is_public', true);

            $club = Club::create([
                'name' => $request->name,
                'address' => $request->address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'logo_url' => $logoPath,
                'status' => $status,
                'is_public' => $isPublic,
                'created_by' => $userId,
            ]);

            if ($coverPath) {
                ClubProfile::create([
                    'club_id' => $club->id,
                    'cover_image_url' => $coverPath,
                ]);
            }

            ClubMember::create([
                'club_id' => $club->id,
                'user_id' => $userId,
                'role' => ClubMemberRole::Admin,
                'membership_status' => ClubMembershipStatus::Joined,
                'status' => ClubMemberStatus::Active,
                'joined_at' => now(),
            ]);

            $club->load([
                'members.user' => function ($query) {
                    $query->with(User::FULL_RELATIONS);
                },
                'profile',
                'creator'
            ]);

            $message = $status === ClubStatus::Draft->value ? 'Lưu bản nháp CLB thành công' : 'Tạo câu lạc bộ thành công';
            return ResponseHelper::success(new ClubResource($club), $message);
        });
    }

    public function show($clubId)
    {
        $club = Club::withFullRelations()->findOrFail($clubId);

        if (!$club->is_public) {
            $userId = auth()->id();

            if (!$userId) {
                return ResponseHelper::error('CLB này là riêng tư. Bạn cần đăng nhập để xem', 401);
            }

            $isCreator = $club->created_by === $userId;
            $isMember = $club->members()
                ->where('user_id', $userId)
                ->where('membership_status', ClubMembershipStatus::Joined)
                ->where('status', ClubMemberStatus::Active)
                ->exists();

            if (!$isCreator && !$isMember) {
                return ResponseHelper::error('Bạn không có quyền xem CLB riêng tư này', 403);
            }
        }

        $members = $club->joinedMembers()->with(['user' => User::FULL_RELATIONS])->get();

        $members = $members->map(function ($member) {
            $user = $member->user;
            $score = 0;
            if ($user && $user->relationLoaded('sports')) {
                foreach ($user->sports ?? [] as $us) {
                    $vndupr = $us->relationLoaded('scores')
                        ? $us->scores->where('score_type', 'vndupr_score')->sortByDesc('created_at')->first()
                        : null;
                    if ($vndupr) {
                        $score = (float) $vndupr->score_value;
                        break;
                    }
                }

                foreach ($user->sports ?? [] as $userSport) {
                    $stats = $this->calculateWinRateAndPerformance($user->id, $userSport->sport_id);
                    $userSport->setAttribute('win_rate', $stats['win_rate']);
                    $userSport->setAttribute('performance', $stats['performance']);
                }
            }
            $member->user?->setAttribute('club_score', $score);
            return $member;
        })->sortByDesc(fn ($m) => $m->user?->club_score ?? 0)->values();

        $members->each(fn ($member, $index) => $member->setAttribute('rank_in_club', $index + 1));

        $club->setRelation('members', $members);

        return ResponseHelper::success(new ClubResource($club), 'Lấy thông tin câu lạc bộ thành công');
    }

    public function update(Request $request, $clubId)
    {
        if ($request->has('zalo_enabled')) {
            $request->merge(['zalo_enabled' => filter_var($request->zalo_enabled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
        }
        if ($request->has('qr_code_enabled')) {
            $request->merge(['qr_code_enabled' => filter_var($request->qr_code_enabled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
        }
        if ($request->has('is_public')) {
            $request->merge(['is_public' => filter_var($request->is_public, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)]);
        }

        $request->validate([
            'name' => "nullable|string|max:255|unique:clubs,name,{$clubId},id,deleted_at,NULL",
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'logo_url' => 'nullable|image|max:2048',
            'cover_image_url' => 'nullable|image|max:2048',
            'status' => ['nullable', Rule::enum(ClubStatus::class)],
            'is_public' => 'nullable|boolean',
            'description' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|url|max:255',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'zalo_link' => 'nullable|string|max:500',
            'zalo_enabled' => 'nullable|boolean',
            'qr_code_image_url' => 'nullable|image|mimes:png,jpg,jpeg,gif|max:5120', // 5MB
            'qr_code_enabled' => 'nullable|boolean',
        ]);

        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền cập nhật CLB', 403);
        }

        $updatableFields = [
            'name', 'address', 'latitude', 'longitude', 'logo_url', 'status', 'is_public',
            'cover_image_url', 'description', 'phone', 'email', 'website', 'city', 'province', 'country',
            'zalo_link', 'zalo_enabled', 'qr_code_enabled'
        ];

        $hasAnyField = $request->hasAny($updatableFields) ||
                       $request->hasFile('logo_url') ||
                       $request->hasFile('cover_image_url') ||
                       $request->hasFile('qr_code_image_url');

        if (!$hasAnyField) {
            return ResponseHelper::error('Không có trường nào được gửi lên để cập nhật', 400);
        }

        return DB::transaction(function () use ($request, $club) {
            $logoPath = $club->getRawOriginal('logo_url');
            if ($request->hasFile('logo_url')) {
                if ($logoPath) {
                    $this->imageService->deleteOldImage($logoPath);
                }
                $logoPath = $this->imageService->optimize($request->file('logo_url'), 'logos');
            }

            $club->update([
                'name' => $request->name ?? $club->name,
                'address' => $request->address ?? $club->address,
                'latitude' => $request->latitude ?? $club->latitude,
                'longitude' => $request->longitude ?? $club->longitude,
                'logo_url' => $logoPath,
                'status' => $request->status ?? $club->status,
                'is_public' => $request->has('is_public') ? $request->boolean('is_public') : $club->is_public,
            ]);

            $profile = $club->profile;
            if ($request->hasFile('cover_image_url')) {
                if ($profile && $profile->getRawCoverImagePath()) {
                    $this->imageService->deleteOldImage($profile->getRawCoverImagePath());
                }
                $coverPath = $this->imageService->optimize($request->file('cover_image_url'), 'covers');

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
            if ($request->hasFile('qr_code_image_url')) {
                if (!$profile) {
                    $profile = $club->profile;
                }

                if ($profile && $profile->getRawQrCodeImagePath()) {
                    $this->imageService->deleteOldImage($profile->getRawQrCodeImagePath());
                }

                $qrCodePath = $this->imageService->optimizeThumbnail($request->file('qr_code_image_url'), 'qr_codes', 90);

                if ($profile) {
                    $profile->update(['qr_code_image_url' => $qrCodePath]);
                } else {
                    $profile = ClubProfile::create([
                        'club_id' => $club->id,
                        'qr_code_image_url' => $qrCodePath,
                    ]);
                }
            }

            if ($request->hasAny(['description', 'phone', 'email', 'website', 'city', 'province', 'country', 'zalo_link', 'zalo_enabled', 'qr_code_enabled'])) {
                if (!$profile) {
                    $profile = $club->profile;
                }

                // Prepare social_links and settings updates (ensure they are objects, not arrays)
                $socialLinks = $profile && is_array($profile->social_links) ? $profile->social_links : [];
                $settings = $profile && is_array($profile->settings) ? $profile->settings : [];

                // Update Zalo link in social_links
                if ($request->has('zalo_link')) {
                    if ($request->zalo_link) {
                        $socialLinks['zalo'] = $request->zalo_link;
                    } else {
                        // Remove zalo link if empty
                        unset($socialLinks['zalo']);
                    }
                }

                // Update Zalo enabled in settings
                if ($request->has('zalo_enabled')) {
                    $settings['zalo_enabled'] = $request->boolean('zalo_enabled');
                }

                // Update QR code enabled in settings
                if ($request->has('qr_code_enabled')) {
                    $settings['qr_code_enabled'] = $request->boolean('qr_code_enabled');
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
                        'description' => $request->description ?? $profile->description,
                        'phone' => $request->phone ?? $profile->phone,
                        'email' => $request->email ?? $profile->email,
                        'website' => $request->website ?? $profile->website,
                        'city' => $request->city ?? $profile->city,
                        'province' => $request->province ?? $profile->province,
                        'country' => $request->country ?? $profile->country,
                        'social_links' => $socialLinks,
                        'settings' => $settings,
                    ]);
                } else {
                    ClubProfile::create([
                        'club_id' => $club->id,
                        'description' => $request->description,
                        'phone' => $request->phone,
                        'email' => $request->email,
                        'website' => $request->website,
                        'city' => $request->city,
                        'province' => $request->province,
                        'country' => $request->country,
                        'social_links' => $socialLinks,
                        'settings' => $settings,
                    ]);
                }
            }

            $club->refresh()->load([
                'members.user' => function ($query) {
                    $query->with(User::FULL_RELATIONS);
                },
                'profile',
                'creator'
            ]);

            return ResponseHelper::success(new ClubResource($club), 'Cập nhật câu lạc bộ thành công');
        });
    }

    public function destroy($clubId)
    {
        $club = Club::with('profile')->findOrFail($clubId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền xóa CLB', 403);
        }

        return DB::transaction(function () use ($club) {
            $logoPath = $club->getRawOriginal('logo_url');
            if ($logoPath) {
                $this->imageService->deleteOldImage($logoPath);
            }

            if ($club->profile) {
                $coverPath = $club->profile->getRawCoverImagePath();
                if ($coverPath) {
                    $this->imageService->deleteOldImage($coverPath);
                }
            }

            $club->delete();

            return ResponseHelper::success([], 'Xóa câu lạc bộ thành công');
        });
    }

    public function restore($clubId)
    {
        $club = Club::onlyTrashed()->findOrFail($clubId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        $isCreator = $club->created_by === $userId;
        $isSystemAdmin = User::isAdmin($userId);

        if (!$isCreator && !$isSystemAdmin) {
            return ResponseHelper::error('Chỉ người tạo CLB hoặc admin hệ thống mới có quyền khôi phục CLB', 403);
        }

        $club->restore();
        $club->refresh()->load([
            'members.user' => function ($query) {
                $query->with(User::FULL_RELATIONS);
            },
            'profile',
            'creator'
        ]);

        return ResponseHelper::success(
            new ClubResource($club),
            'Khôi phục câu lạc bộ thành công. Lưu ý: Tên CLB đã được thay đổi để tránh trùng lặp. Bạn có thể cập nhật lại tên nếu cần.'
        );
    }

    public function leave(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        $member = $club->activeMembers()->where('user_id', $userId)->first();

        if (!$member) {
            return ResponseHelper::error('Bạn không phải thành viên active của CLB này', 404);
        }

        // Validation: Nếu là admin và là admin duy nhất, phải nhượng lại cho thành viên khác
        if ($member->role === ClubMemberRole::Admin) {
            $adminCount = $club->countActiveAdmins();

            if ($adminCount === 1) {
                // Admin duy nhất, bắt buộc phải transfer ownership
                $validated = $request->validate([
                    'transfer_to_user_id' => 'required|integer|exists:users,id',
                ], [
                    'transfer_to_user_id.required' => 'Bạn là admin duy nhất của CLB. Vui lòng nhượng lại quyền quản lý cho thành viên khác trước khi rời.',
                    'transfer_to_user_id.exists' => 'Người dùng không tồn tại.',
                ]);

                $newAdmin = $club->activeMembers()
                    ->where('user_id', $validated['transfer_to_user_id'])
                    ->where('id', '!=', $member->id)
                    ->with('user')
                    ->first();

                if (!$newAdmin) {
                    return ResponseHelper::error('Người được nhượng quyền phải là thành viên active của CLB và không phải chính bạn', 400);
                }

                return DB::transaction(function () use ($member, $newAdmin) {
                    // Promote member thành admin
                    $newAdmin->update([
                        'role' => ClubMemberRole::Admin,
                    ]);

                    // Admin hiện tại rời CLB
                    $member->update([
                        'membership_status' => ClubMembershipStatus::Left,
                        'status' => ClubMemberStatus::Inactive,
                        'left_at' => now(),
                    ]);

                    return ResponseHelper::success([
                        'transferred_to' => [
                            'user_id' => $newAdmin->user_id,
                            'user_name' => $newAdmin->user->full_name ?? 'N/A',
                        ],
                    ], 'Bạn đã nhượng quyền quản lý và rời CLB thành công');
                });
            }
            // Có nhiều admin khác, có thể rời bình thường
        }

        // Member thường hoặc admin có nhiều admin khác → rời bình thường
        $member->update([
            'membership_status' => ClubMembershipStatus::Left,
            'status' => ClubMemberStatus::Inactive,
            'left_at' => now(),
        ]);

        return ResponseHelper::success([], 'Bạn đã rời CLB');
    }

    public function myClubs(Request $request)
    {
        $userId = auth()->id();
        $validated = $request->validate([
            'role' => ['sometimes', Rule::enum(ClubMemberRole::class)],
        ]);

        $query = Club::whereHas('members', function ($q) use ($userId, $validated) {
            $q->where('user_id', $userId)
              ->where('membership_status', ClubMembershipStatus::Joined)
              ->where('status', ClubMemberStatus::Active);
            if (!empty($validated['role'])) {
                $q->where('role', $validated['role']);
            }
        });

        $clubs = $query->withFullRelations()->get();

        return ResponseHelper::success(ClubResource::collection($clubs), 'Lấy danh sách câu lạc bộ của tôi thành công');
    }

    public function getProfile($clubId)
    {
        $club = Club::with(['profile', 'creator'])->findOrFail($clubId);

        return ResponseHelper::success([
            'club_id' => $club->id,
            'name' => $club->name,
            'address' => $club->address,
            'latitude' => $club->latitude,
            'longitude' => $club->longitude,
            'logo_url' => $club->logo_url,
            'status' => $club->status,
            'profile' => $club->profile,
        ], 'Lấy thông tin profile CLB thành công');
    }

    public function getFund($clubId)
    {
        $club = Club::with(['wallets', 'mainWallet'])->findOrFail($clubId);
        $mainWallet = $club->mainWallet;

        $fund = [
            'club_id' => $club->id,
            'main_wallet_id' => $mainWallet?->id,
            'balance' => $mainWallet?->balance ?? 0,
            'currency' => $mainWallet?->currency ?? 'VND',
            'qr_code_url' => $mainWallet?->qr_code_url,
            'total_wallets' => $club->wallets()->count(),
        ];

        return ResponseHelper::success($fund, 'Lấy thông tin quỹ CLB thành công');
    }

    public function updateFund(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManageFinance($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/treasurer mới có quyền cập nhật quỹ', 403);
        }

        $validated = $request->validate([
            'qr_code_url' => 'required|string|url',
        ]);

        $mainWallet = $club->mainWallet;
        if (!$mainWallet) {
            return ResponseHelper::error('CLB chưa có ví chính', 404);
        }

        $mainWallet->update(['qr_code_url' => $validated['qr_code_url']]);

        return ResponseHelper::success([
            'club_id' => $club->id,
            'main_wallet_id' => $mainWallet->id,
            'qr_code_url' => $mainWallet->qr_code_url,
        ], 'Cập nhật thông tin quỹ CLB thành công');
    }

    public function verify(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $user = auth()->user();

        if (!$user || $user->role !== User::ADMIN) {
            return ResponseHelper::error('Chỉ admin hệ thống mới có quyền verify CLB', 403);
        }

        $validated = $request->validate([
            'is_verified' => 'required|boolean',
        ]);

        $club->update(['is_verified' => $validated['is_verified']]);

        $message = $validated['is_verified']
            ? 'Xác minh CLB thành công'
            : 'Hủy xác minh CLB thành công';

        return ResponseHelper::success(
            new ClubResource($club->refresh()),
            $message
        );
    }

    public function searchLocation(Request $request, GeocodingService $geocoder)
    {
        $validated = $request->validate([
            'query' => 'required|string|max:255',
        ]);

        $results = $geocoder->search($validated['query']);

        return ResponseHelper::success($results, 'Tìm kiếm địa điểm thành công');
    }

    public function detailGooglePlace(Request $request, GeocodingService $geocoder)
    {
        $validated = $request->validate([
            'place_id' => 'required|string|max:255',
        ]);

        $result = $geocoder->getGooglePlaceDetail($validated['place_id']);

        return ResponseHelper::success($result, 'Lấy chi tiết địa điểm thành công');
    }

    private function calculateWinRateAndPerformance($userId, $sportId): array
    {
        $histories = VnduprHistory::where('user_id', $userId)
            ->latest()
            ->take(10)
            ->get();

        $uniqueHistories = collect();
        $seen = [];
        foreach ($histories as $h) {
            $key = $h->match_id ? 'match_' . $h->match_id : 'mini_' . $h->mini_match_id;
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueHistories->push($h);
            }
        }

        $totalMatches = $uniqueHistories->count();
        $wins = 0;
        $totalPoint = 0;

        if ($totalMatches > 0) {
            $matchIds = $uniqueHistories->pluck('match_id')->filter()->unique()->values()->all();
            $miniIds  = $uniqueHistories->pluck('mini_match_id')->filter()->unique()->values()->all();

            $matches = Matches::whereIn('id', $matchIds)->get()->keyBy('id');
            $minis = MiniMatch::withFullRelations()->whereIn('id', $miniIds)->get()->keyBy('id');

            $teamIds = $matches->pluck('winner_id')->filter()->unique()->values()->all();
            $teamMembersByTeam = collect();
            if (!empty($teamIds)) {
                $members = DB::table('team_members')
                    ->whereIn('team_id', $teamIds)
                    ->get();
                $teamMembersByTeam = $members->groupBy('team_id')
                    ->map(fn($rows) => $rows->pluck('user_id')->flip());
            }

            $miniTeamMembersByTeam = DB::table('mini_team_members')
                ->whereIn(
                    'mini_team_id',
                    $minis->pluck('team1_id')
                        ->merge($minis->pluck('team2_id'))
                        ->filter()
                        ->unique()
                )
                ->get()
                ->groupBy('mini_team_id')
                ->map(fn($rows) => $rows->pluck('user_id')->flip());

            foreach ($uniqueHistories->values() as $index => $history) {
                $isWin = false;

                if ($history->match_id) {
                    $match = $matches->get($history->match_id);
                    if ($match && $match->winner_id) {
                        $teamMembers = $teamMembersByTeam->get($match->winner_id);
                        $isWin = $teamMembers ? $teamMembers->has($userId) : false;
                    }
                }
                elseif ($history->mini_match_id) {
                    $mini = $minis->get($history->mini_match_id);
                    if ($mini && $mini->team_win_id) {
                        $winningTeamMembers = $miniTeamMembersByTeam->get($mini->team_win_id);
                        $isWin = $winningTeamMembers ? $winningTeamMembers->has($userId) : false;
                    }
                }

                if ($isWin) {
                    $wins++;
                    $coef = $index < 3 ? 1.5 : 1.0;
                    $totalPoint += 10 * $coef;
                }
            }
        }

        // Tính win_rate
        $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 2) : 0;

        // Tính performance
        $maxPoint = 0;
        for ($i = 0; $i < $totalMatches; $i++) {
            $maxPoint += $i < 3 ? 15 : 10;
        }
        $performance = $maxPoint > 0 ? round(($totalPoint / $maxPoint) * 100, 2) : 0;

        return [
            'win_rate' => $winRate,
            'performance' => $performance,
        ];
    }

    public function getMonthlyLeaderboard(Request $request, $clubId)
    {
        $validated = $request->validate([
            'month' => 'sometimes|integer|min:1|max:12',
            'year' => 'sometimes|integer|min:2020|max:' . (date('Y') + 1),
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $month = $validated['month'] ?? now()->month;
        $year = $validated['year'] ?? now()->year;
        $perPage = $validated['per_page'] ?? 50;

        $requestedDate = Carbon::create($year, $month, 1);
        if ($requestedDate->isFuture() && !$requestedDate->isCurrentMonth()) {
            return ResponseHelper::error('Không thể xem bảng xếp hạng của tháng trong tương lai', 400);
        }

        $club = Club::findOrFail($clubId);
        $members = $club->joinedMembers()->with(['user.sports.scores'])->get();

        if ($members->isEmpty()) {
            return ResponseHelper::success([
                'club_info' => [
                    'id' => $club->id,
                    'name' => $club->name,
                    'member_count' => 0,
                ],
                'period' => [
                    'month' => $month,
                    'year' => $year,
                    'label' => "Tháng {$month}/{$year}",
                ],
                'updated_at' => now()->toISOString(),
                'leaderboard' => [],
            ], 'Bảng xếp hạng câu lạc bộ');
        }

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $memberIds = $members->pluck('user_id')->filter()->unique();

        $histories = VnduprHistory::whereIn('user_id', $memberIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('user_id');

        $matchIds = $histories->flatten()->pluck('match_id')->filter()->unique();
        $miniMatchIds = $histories->flatten()->pluck('mini_match_id')->filter()->unique();

        $matches = Matches::with(['homeTeam.members', 'awayTeam.members'])
            ->whereIn('id', $matchIds)
            ->get()
            ->keyBy('id');

        $miniMatches = MiniMatch::whereIn('id', $miniMatchIds)
            ->get()
            ->keyBy('id');

        $miniTeamMembersByTeam = collect();
        if ($miniMatches->isNotEmpty()) {
            $miniTeamIds = $miniMatches->pluck('team1_id')
                ->merge($miniMatches->pluck('team2_id'))
                ->filter()
                ->unique();

            $miniTeamMembersByTeam = DB::table('mini_team_members')
                ->whereIn('mini_team_id', $miniTeamIds)
                ->get()
                ->groupBy('mini_team_id')
                ->map(fn($rows) => $rows->pluck('user_id')->all());
        }

        $leaderboardData = $members->map(function ($member) use ($histories, $matches, $miniMatches, $miniTeamMembersByTeam) {
            $userId = $member->user_id;
            $userHistories = $histories->get($userId, collect());

            $finalScore = 0;
            if ($userHistories->isNotEmpty()) {
                $finalScore = $userHistories->last()->score_after;
            } else {
                $vnduprScore = $member->user?->sports->flatMap(fn($sport) => $sport->scores)
                    ->where('score_type', 'vndupr_score')
                    ->sortByDesc('created_at')
                    ->first();
                $finalScore = $vnduprScore ? $vnduprScore->score_value : 0;
            }

            $matchesPlayed = $userHistories->count();
            $wins = 0;
            $losses = 0;
            $scoreChange = 0;

            if ($matchesPlayed > 0) {
                $scoreChange = $userHistories->last()->score_after - $userHistories->first()->score_before;

                foreach ($userHistories as $history) {
                    $isWin = false;

                    if ($history->match_id && $matches->has($history->match_id)) {
                        $match = $matches->get($history->match_id);
                        $homeUserIds = $match->homeTeam->members->pluck('id')->all();
                        $awayUserIds = $match->awayTeam->members->pluck('id')->all();

                        $isWin = (
                            ($match->winner_id == $match->home_team_id && in_array($userId, $homeUserIds)) ||
                            ($match->winner_id == $match->away_team_id && in_array($userId, $awayUserIds))
                        );
                    } elseif ($history->mini_match_id && $miniMatches->has($history->mini_match_id)) {
                        $mini = $miniMatches->get($history->mini_match_id);
                        $team1Members = $miniTeamMembersByTeam[$mini->team1_id] ?? [];
                        $team2Members = $miniTeamMembersByTeam[$mini->team2_id] ?? [];

                        $isWin = (
                            (in_array($userId, $team1Members) && $mini->team_win_id == $mini->team1_id) ||
                            (in_array($userId, $team2Members) && $mini->team_win_id == $mini->team2_id)
                        );
                    }

                    if ($isWin) {
                        $wins++;
                    } else {
                        $losses++;
                    }
                }
            }

            $winRate = $matchesPlayed > 0 ? round(($wins / $matchesPlayed) * 100, 2) : 0;

            return [
                'member_id' => $member->id,
                'user_id' => $userId,
                'user' => $member->user,
                'vndupr_score' => round($finalScore, 3),
                'monthly_stats' => [
                    'matches_played' => $matchesPlayed,
                    'wins' => $wins,
                    'losses' => $losses,
                    'win_rate' => $winRate,
                    'score_change' => round($scoreChange, 3),
                ],
            ];
        });

        $sortedLeaderboard = $leaderboardData->sortByDesc('vndupr_score')->values();

        $rankedLeaderboard = $sortedLeaderboard->map(function ($item, $index) {
            $item['rank'] = $index + 1;
            return $item;
        });

        $total = $rankedLeaderboard->count();
        $currentPage = max(1, (int) $request->query('page', 1));
        $lastPage = ceil($total / $perPage);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedData = $rankedLeaderboard->slice($offset, $perPage)->values();

        $response = [
            'club_info' => [
                'id' => $club->id,
                'name' => $club->name,
                'member_count' => $members->count(),
            ],
            'period' => [
                'month' => $month,
                'year' => $year,
                'label' => "Tháng {$month}/{$year}",
            ],
            'updated_at' => now()->toISOString(),
            'leaderboard' => ClubLeaderboardResource::collection($paginatedData),
        ];

        $meta = [
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
        ];

        return ResponseHelper::success($response, 'Lấy bảng xếp hạng thành công', 200, $meta);
    }
}
