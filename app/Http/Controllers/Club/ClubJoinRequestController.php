<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Helpers\ResponseHelper;
use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClubJoinRequestController extends Controller
{
    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền xem yêu cầu tham gia', 403);
        }

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'status' => 'sometimes|in:pending,approved,rejected,cancelled',
        ]);

        $query = $club->members()->where('status', ClubMemberStatus::Pending)
            ->with(['user', 'reviewer']);

        if (!empty($validated['status'])) {
            if ($validated['status'] === 'approved') {
                $query->where('status', ClubMemberStatus::Active)->whereNotNull('reviewed_at');
            } elseif ($validated['status'] === 'rejected') {
                $query->where('status', ClubMemberStatus::Inactive)->whereNotNull('rejection_reason');
            } elseif ($validated['status'] === 'cancelled') {
                $query->whereNotNull('left_at');
            } else {
                $query->where('status', ClubMemberStatus::Pending);
            }
        }

        $perPage = $validated['per_page'] ?? 15;
        $requests = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return ResponseHelper::success([
            'data' => $requests->items(),
            'current_page' => $requests->currentPage(),
            'per_page' => $requests->perPage(),
            'total' => $requests->total(),
            'last_page' => $requests->lastPage(),
        ], 'Lấy danh sách yêu cầu tham gia thành công');
    }

    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập để gửi yêu cầu tham gia', 401);
        }

        // Check nếu đã là member (bất kỳ status nào)
        if ($club->hasMember($userId)) {
            return ResponseHelper::error('Bạn đã là thành viên của CLB này', 409);
        }

        $validated = $request->validate([
            'message' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($club, $userId, $validated) {
            // Check duplicate pending request trong transaction để tránh race condition
            $existingRequest = $club->members()
                ->where('user_id', $userId)
                ->where('status', ClubMemberStatus::Pending)
                ->lockForUpdate()
                ->first();

            if ($existingRequest) {
                return ResponseHelper::error('Bạn đã gửi yêu cầu tham gia. Vui lòng chờ duyệt', 409);
            }

            $member = ClubMember::create([
                'club_id' => $club->id,
                'user_id' => $userId,
                'role' => ClubMemberRole::Member,
                'status' => ClubMemberStatus::Pending,
                'message' => $validated['message'] ?? null,
            ]);

            $member->load(['user', 'club']);

            return ResponseHelper::success($member, 'Yêu cầu tham gia đã được gửi', 201);
        });
    }

    public function show($clubId, $requestId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->where('status', ClubMemberStatus::Pending)
            ->with(['user', 'club', 'reviewer'])
            ->findOrFail($requestId);

        return ResponseHelper::success($member, 'Lấy chi tiết yêu cầu thành công');
    }

    public function destroy($clubId, $requestId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->where('status', ClubMemberStatus::Pending)
            ->findOrFail($requestId);

        $userId = auth()->id();

        if ($member->user_id !== $userId && !$member->club->canManage($userId)) {
            return ResponseHelper::error('Chỉ người gửi hoặc admin mới có quyền hủy yêu cầu', 403);
        }

        $member->delete();

        return ResponseHelper::success([], 'Yêu cầu đã được hủy');
    }

    public function approve(Request $request, $clubId, $requestId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->where('status', ClubMemberStatus::Pending)
            ->findOrFail($requestId);

        $club = $member->club;
        $userId = auth()->id();

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền duyệt', 403);
        }

        $validated = $request->validate([
            'role' => ['sometimes', Rule::enum(ClubMemberRole::class)],
        ]);

        $member->update([
            'status' => ClubMemberStatus::Active,
            'role' => $validated['role'] ?? ClubMemberRole::Member,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'joined_at' => now(),
        ]);

        $member->load(['user', 'reviewer']);

        return ResponseHelper::success($member, 'Yêu cầu đã được duyệt');
    }

    public function reject(Request $request, $clubId, $requestId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->where('status', ClubMemberStatus::Pending)
            ->findOrFail($requestId);

        $club = $member->club;
        $userId = auth()->id();

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền từ chối', 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $member->update([
            'status' => ClubMemberStatus::Inactive,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $member->load(['user', 'reviewer']);

        return ResponseHelper::success($member, 'Yêu cầu đã bị từ chối');
    }
}
