<?php

namespace App\Http\Controllers;

use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Enums\ClubMembershipStatus;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use App\Models\Club\ClubProfile;
use App\Models\User;
use App\Http\Resources\ClubResource;
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
        $query = Club::withFullRelations()->orderBy('created_at', 'desc');

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
            // Figma: Tên câu lạc bộ
            'name' => 'required|string|max:255|unique:clubs',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'logo_url' => 'required|image|max:2048',
            'cover_image_url' => 'required|image|max:2048',
        ]);

        $userId = auth()->id();
        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập để tạo CLB', 401);
        }

        $logoFile = $request->file('logo_url');
        $coverFile = $request->file('cover_image_url');
        if (!$logoFile || !$coverFile) {
            return ResponseHelper::error('Vui lòng gửi đầy đủ ảnh logo và ảnh bìa (form-data, type File)', 422);
        }

        return DB::transaction(function () use ($request, $userId, $logoFile, $coverFile) {
            // Optimize và upload ảnh
            $logoPath = $this->imageService->optimize($logoFile, 'logos');
            $coverPath = $this->imageService->optimize($coverFile, 'covers');

            $status = $request->input('status', 'active');
            $isPublic = $request->boolean('is_public', true);

            // Tạo club
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

            // Tạo club profile với cover image
            ClubProfile::create([
                'club_id' => $club->id,
                'cover_image_url' => $coverPath,
            ]);

            // Tự động tạo member với role admin cho người tạo CLB
            ClubMember::create([
                'club_id' => $club->id,
                'user_id' => $userId,
                'role' => ClubMemberRole::Admin,
                'membership_status' => ClubMembershipStatus::Joined,
                'status' => ClubMemberStatus::Active,
                'joined_at' => now(),
            ]);

            // Load relations để trả về đầy đủ data
            $club->load([
                'members.user' => function ($query) {
                    $query->with(User::FULL_RELATIONS);
                },
                'profile',
                'creator'
            ]);

            $message = $status === 'draft' ? 'Lưu bản nháp CLB thành công' : 'Tạo câu lạc bộ thành công';
            return ResponseHelper::success(new ClubResource($club), $message);
        });
    }

    public function show($clubId)
    {
        $club = Club::withFullRelations()->findOrFail($clubId);

        // Chỉ hiển thị thành viên đã join (membership_status = joined)
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
        // 1. Validation - CHỈ require name
        $request->validate([
            'name' => "nullable|string|max:255|unique:clubs,name,{$clubId}",
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'logo_url' => 'nullable|image|max:2048',
            'cover_image_url' => 'nullable|image|max:2048',
            'status' => 'nullable|in:active,inactive,draft',
            'is_public' => 'nullable|boolean',
            // Profile fields
            'description' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|url|max:255',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ]);

        // 2. Kiểm tra quyền
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền cập nhật CLB', 403);
        }

        // 3. Kiểm tra có field nào được gửi lên không
        $updatableFields = [
            'name', 'address', 'latitude', 'longitude', 'logo_url', 'status', 'is_public',
            'cover_image_url', 'description', 'phone', 'email', 'website', 'city', 'province', 'country'
        ];

        $hasAnyField = $request->hasAny($updatableFields) ||
                       $request->hasFile('logo_url') ||
                       $request->hasFile('cover_image_url');

        if (!$hasAnyField) {
            return ResponseHelper::error('Không có trường nào được gửi lên để cập nhật', 400);
        }

        // 4. Transaction - Xử lý giống store()
        return DB::transaction(function () use ($request, $club) {
            // 4.1. Xử lý logo_url (nếu có file mới)
            $logoPath = $club->getRawOriginal('logo_url');
            if ($request->hasFile('logo_url')) {
                // Xóa ảnh cũ
                if ($logoPath) {
                    $this->imageService->deleteOldImage($logoPath);
                }
                // Upload ảnh mới
                $logoPath = $this->imageService->optimize($request->file('logo_url'), 'logos');
            }

            // 4.2. Update bảng clubs
            $club->update([
                'name' => $request->name ?? $club->name,
                'address' => $request->address ?? $club->address,
                'latitude' => $request->latitude ?? $club->latitude,
                'longitude' => $request->longitude ?? $club->longitude,
                'logo_url' => $logoPath,
                'status' => $request->status ?? $club->status,
                'is_public' => $request->has('is_public') ? $request->boolean('is_public') : $club->is_public,
            ]);

            // 4.3. Xử lý cover_image_url (nếu có file mới)
            $profile = $club->profile;
            if ($request->hasFile('cover_image_url')) {
                // Xóa ảnh cũ
                if ($profile && $profile->getRawCoverImagePath()) {
                    $this->imageService->deleteOldImage($profile->getRawCoverImagePath());
                }
                // Upload ảnh mới
                $coverPath = $this->imageService->optimize($request->file('cover_image_url'), 'covers');

                // Update hoặc create profile
                if ($profile) {
                    $profile->update(['cover_image_url' => $coverPath]);
                } else {
                    $profile = ClubProfile::create([
                        'club_id' => $club->id,
                        'cover_image_url' => $coverPath,
                    ]);
                }
            }

            // 4.4. Update các field profile khác
            if ($request->hasAny(['description', 'phone', 'email', 'website', 'city', 'province', 'country'])) {
                // Load lại profile nếu chưa có
                if (!$profile) {
                    $profile = $club->profile;
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
                    ]);
                } else {
                    // Tạo profile mới nếu chưa có
                    ClubProfile::create([
                        'club_id' => $club->id,
                        'description' => $request->description,
                        'phone' => $request->phone,
                        'email' => $request->email,
                        'website' => $request->website,
                        'city' => $request->city,
                        'province' => $request->province,
                        'country' => $request->country,
                    ]);
                }
            }

            // 4.5. Load relations và return
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
        // 1. Kiểm tra quyền
        $club = Club::with('profile')->findOrFail($clubId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền xóa CLB', 403);
        }

        // 2. Transaction - Xóa ảnh trước khi xóa record
        return DB::transaction(function () use ($club) {
            // 2.1. Xóa logo_url
            $logoPath = $club->getRawOriginal('logo_url');
            if ($logoPath) {
                $this->imageService->deleteOldImage($logoPath);
            }

            // 2.2. Xóa cover_image_url từ profile
            if ($club->profile) {
                $coverPath = $club->profile->getRawCoverImagePath();
                if ($coverPath) {
                    $this->imageService->deleteOldImage($coverPath);
                }
            }

            // 2.3. Xóa club (cascade sẽ xóa profile và members nếu đã config trong migration)
            $club->delete();

            return ResponseHelper::success([], 'Xóa câu lạc bộ thành công');
        });
    }

    /**
     * User rời CLB (chỉ thành viên active mới được gọi)
     * POST /api/clubs/{clubId}/leave
     */
    public function leave($clubId)
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
}
