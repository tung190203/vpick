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

class ClubMemberController extends Controller
{
    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:255',
            'role' => ['sometimes', Rule::enum(ClubMemberRole::class)],
            'status' => ['sometimes', Rule::enum(ClubMemberStatus::class)],
        ]);

        $query = $club->members()->with(['user', 'reviewer']);

        if (!empty($validated['search'])) {
            $query->whereHas('user', function ($q) use ($validated) {
                $q->where('full_name', 'LIKE', '%' . $validated['search'] . '%');
            });
        }

        if (!empty($validated['role'])) {
            $query->where('role', $validated['role']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $members = $query->paginate($perPage);

        $statistics = [
            'total' => $club->members()->count(),
            'by_role' => [
                'admin' => $club->members()->where('role', ClubMemberRole::Admin)->count(),
                'manager' => $club->members()->where('role', ClubMemberRole::Manager)->count(),
                'treasurer' => $club->members()->where('role', ClubMemberRole::Treasurer)->count(),
                'secretary' => $club->members()->where('role', ClubMemberRole::Secretary)->count(),
                'member' => $club->members()->where('role', ClubMemberRole::Member)->count(),
            ],
            'by_status' => [
                'pending' => $club->members()->where('status', ClubMemberStatus::Pending)->count(),
                'active' => $club->members()->where('status', ClubMemberStatus::Active)->count(),
                'inactive' => $club->members()->where('status', ClubMemberStatus::Inactive)->count(),
                'suspended' => $club->members()->where('status', ClubMemberStatus::Suspended)->count(),
            ],
        ];

        return ResponseHelper::success([
            'data' => $members->items(),
            'statistics' => $statistics,
            'current_page' => $members->currentPage(),
            'per_page' => $members->perPage(),
            'total' => $members->total(),
            'last_page' => $members->lastPage(),
        ], 'Lấy danh sách thành viên thành công');
    }

    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        // Chỉ admin/manager mới có quyền thêm member trực tiếp
        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền thêm thành viên', 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => ['sometimes', Rule::enum(ClubMemberRole::class)],
            'position' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'status' => ['sometimes', Rule::enum(ClubMemberStatus::class)],
        ]);

        // Check duplicate member (bất kỳ status nào)
        if ($club->hasMember($validated['user_id'])) {
            return ResponseHelper::error('Người dùng đã là thành viên của CLB này', 409);
        }

        return DB::transaction(function () use ($club, $validated) {
            $member = ClubMember::create([
                'club_id' => $club->id,
                'user_id' => $validated['user_id'],
                'role' => $validated['role'] ?? ClubMemberRole::Member,
                'position' => $validated['position'] ?? null,
                'status' => $validated['status'] ?? ClubMemberStatus::Active,
                'message' => $validated['message'] ?? null,
                'joined_at' => ($validated['status'] ?? ClubMemberStatus::Active) === ClubMemberStatus::Active ? now() : null,
            ]);

            $member->load(['user', 'club']);

            return ResponseHelper::success($member,
                $member->status === 'pending'
                    ? 'Yêu cầu tham gia đã được gửi'
                    : 'Thành viên đã được thêm vào CLB',
                201
            );
        });
    }

    public function show($clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->with(['user', 'club', 'reviewer'])
            ->findOrFail($memberId);

        return ResponseHelper::success($member, 'Lấy thông tin thành viên thành công');
    }

    public function update(Request $request, $clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)->findOrFail($memberId);
        $userId = auth()->id();

        $club = $member->club;
        if (!$club->canManage($userId) && $member->user_id !== $userId) {
            return ResponseHelper::error('Không có quyền thực hiện thao tác này', 403);
        }

        $validated = $request->validate([
            'role' => ['sometimes', Rule::enum(ClubMemberRole::class)],
            'position' => 'nullable|string|max:255',
            'status' => ['sometimes', Rule::enum(ClubMemberStatus::class)],
            'notes' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($member, $validated, $userId) {
            if (isset($validated['status']) && $validated['status'] === ClubMemberStatus::Active && $member->status === ClubMemberStatus::Pending) {
                $member->update([
                    'status' => ClubMemberStatus::Active,
                    'reviewed_by' => $userId,
                    'reviewed_at' => now(),
                    'joined_at' => now(),
                    'role' => $validated['role'] ?? $member->role,
                ]);
            } elseif (isset($validated['rejection_reason']) && $member->status === ClubMemberStatus::Pending) {
                $member->update([
                    'status' => ClubMemberStatus::Inactive,
                    'reviewed_by' => $userId,
                    'reviewed_at' => now(),
                    'rejection_reason' => $validated['rejection_reason'],
                ]);
            } else {
                $member->update($validated);
            }

            $member->load(['user', 'reviewer']);

            return ResponseHelper::success($member, 'Cập nhật thành viên thành công');
        });
    }

    public function destroy($clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)->findOrFail($memberId);
        $userId = auth()->id();

        $club = $member->club;
        if (!$club->canManage($userId) && $member->user_id !== $userId) {
            return ResponseHelper::error('Không có quyền xóa thành viên này', 403);
        }

        $member->delete();

        return ResponseHelper::success([], 'Xóa thành viên thành công');
    }

    public function approve(Request $request, $clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->where('status', ClubMemberStatus::Pending)
            ->findOrFail($memberId);

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

        return ResponseHelper::success($member, 'Yêu cầu tham gia đã được duyệt');
    }

    public function reject(Request $request, $clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->where('status', ClubMemberStatus::Pending)
            ->findOrFail($memberId);

        $club = $member->club;
        $userId = auth()->id();

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền từ chối', 403);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ]);

        $member->update([
            'status' => ClubMemberStatus::Inactive,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $member->load(['user', 'reviewer']);

        return ResponseHelper::success($member, 'Yêu cầu tham gia đã bị từ chối');
    }

    public function statistics($clubId)
    {
        $club = Club::findOrFail($clubId);

        $statistics = [
            'total' => $club->members()->count(),
            'by_role' => [
                'admin' => $club->members()->where('role', ClubMemberRole::Admin)->count(),
                'manager' => $club->members()->where('role', ClubMemberRole::Manager)->count(),
                'treasurer' => $club->members()->where('role', ClubMemberRole::Treasurer)->count(),
                'secretary' => $club->members()->where('role', ClubMemberRole::Secretary)->count(),
                'member' => $club->members()->where('role', ClubMemberRole::Member)->count(),
            ],
            'by_status' => [
                'active' => $club->members()->where('status', ClubMemberStatus::Active)->count(),
                'inactive' => $club->members()->where('status', ClubMemberStatus::Inactive)->count(),
                'suspended' => $club->members()->where('status', ClubMemberStatus::Suspended)->count(),
                'pending' => $club->members()->where('status', ClubMemberStatus::Pending)->count(),
            ],
        ];

        return ResponseHelper::success($statistics, 'Lấy thống kê thành viên thành công');
    }
}
