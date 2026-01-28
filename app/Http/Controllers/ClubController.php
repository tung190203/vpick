<?php

namespace App\Http\Controllers;

use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use App\Http\Resources\ClubResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClubController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
            'perPage' => 'sometimes|integer|min:1|max:200',
        ]);
        $query = Club::withFullRelations()->orderBy('created_at', 'desc');
    
        if (!empty($validated['name'])) {
            $query->search(['name'], $validated['name']);
        }
    
        if (!empty($validated['location'])) {
            $query->search(['location'], $validated['location']);
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
            'name' => 'required|string|max:255|unique:clubs',
            'location' => 'nullable|string',
            'logo_url' => 'nullable|image|max:2048',
        ]);

        $userId = auth()->id();
        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập để tạo CLB', 401);
        }

        return DB::transaction(function () use ($request, $userId) {
            $logoPath = null;
            if ($request->hasFile('logo_url')) {
                $logoPath = $request->file('logo_url')->store('logos', 'public');
            }
            
            $club = Club::create([
                'name' => $request->name,
                'location' => $request->location,
                'logo_url' => $logoPath,
                'status' => 'active',
                'created_by' => $userId,
            ]);
            
            // Tự động tạo member với role admin cho người tạo CLB
            ClubMember::create([
                'club_id' => $club->id,
                'user_id' => $userId,
                'role' => ClubMemberRole::Admin,
                'status' => ClubMemberStatus::Active,
                'joined_at' => now(),
            ]);
            
            $club->load('members.user');

            return ResponseHelper::success(new ClubResource($club), 'Tạo câu lạc bộ thành công');
        });
    }

    public function show($clubId)
    {
        $club = Club::withFullRelations()->findOrFail($clubId);

        // Load members với user và vnduprScores
        $members = $club->members()->with(['user.vnduprScores'])->get();
        
        $members = $members->map(function ($member) {
            $user = $member->user;
            if ($user) {
                $member->user->club_score = $user->vnduprScores?->first()?->score_value ?? 0;
            }
            return $member;
        })->sortByDesc(function ($member) {
            return $member->user?->club_score ?? 0;
        })->values();

        $members->each(function ($member, $index) {
            $member->rank_in_club = $index + 1;
        });

        $club->setRelation('members', $members);

        return ResponseHelper::success(new ClubResource($club), 'Lấy thông tin câu lạc bộ thành công');
    }

    public function update(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền cập nhật CLB', 403);
        }

        $request->validate([
            'name' => "sometimes|string|max:255|unique:clubs,name,{$clubId}",
            'location' => 'nullable|string',
            'logo_url' => 'nullable|image|max:2048',
        ]);

        $logoPath = $club->logo_url;
        if ($request->hasFile('logo_url')) {
            $logoPath = $request->file('logo_url')->store('logos', 'public');
        }

        $club->update([
            'name' => $request->name ?? $club->name,
            'location' => $request->location ?? $club->location,
            'logo_url' => $logoPath ?? $club->logo_url,
        ]);

        return ResponseHelper::success(new ClubResource($club->refresh()), 'Cập nhật câu lạc bộ thành công');
    }

    public function destroy($clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền xóa CLB', 403);
        }

        $club->delete();

        return ResponseHelper::success([], 'Xóa câu lạc bộ thành công');
    }

    public function join(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập để tham gia CLB', 401);
        }

        // Check nếu đã là member (bất kỳ status nào)
        if ($club->hasMember($userId)) {
            return ResponseHelper::error('Người dùng đã là thành viên của câu lạc bộ này', 409);
        }

        // Check nếu đã có pending request
        $existingRequest = $club->members()
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->first();

        if ($existingRequest) {
            return ResponseHelper::error('Bạn đã gửi yêu cầu tham gia. Vui lòng chờ duyệt', 409);
        }

        return DB::transaction(function () use ($club, $userId) {
        // Tạo join request
        $member = ClubMember::create([
            'club_id' => $club->id,
            'user_id' => $userId,
            'role' => ClubMemberRole::Member,
            'status' => ClubMemberStatus::Pending,
        ]);

            $club->load('members.user');

            return ResponseHelper::success(new ClubResource($club), 'Yêu cầu tham gia đã được gửi', 201);
        });
    }

    public function myClubs(Request $request)
    {
        $userId = auth()->id();
        $validated = $request->validate([
            'role' => ['sometimes', Rule::enum(ClubMemberRole::class)],
        ]);

        $query = Club::whereHas('members', function ($q) use ($userId, $validated) {
            $q->where('user_id', $userId)
              ->where('status', 'active');
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
            'location' => $club->location,
            'logo_url' => $club->logo_url,
            'status' => $club->status,
            'profile' => $club->profile,
        ], 'Lấy thông tin profile CLB thành công');
    }

    public function updateProfile(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền cập nhật profile', 403);
        }

        $validated = $request->validate([
            'description' => 'nullable|string',
            'cover_image_url' => 'nullable|string|url',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|string|url|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'social_links' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $profile = $club->profile;
        if (!$profile) {
            $profile = $club->profile()->create($validated);
        } else {
            $profile->update($validated);
        }

        $club->load('profile');

        return ResponseHelper::success([
            'club_id' => $club->id,
            'name' => $club->name,
            'profile' => $club->profile,
        ], 'Cập nhật profile CLB thành công');
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
}
