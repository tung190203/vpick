<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\Club;
use App\Models\ClubMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClubMemberController extends Controller
{
    /**
     * Lấy danh sách thành viên CLB
     */
    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        
        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:255',
            'role' => 'sometimes|in:member,admin,manager,treasurer,secretary',
            'status' => 'sometimes|in:pending,active,inactive,suspended',
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

        // Thống kê
        $statistics = [
            'total' => $club->members()->count(),
            'by_role' => [
                'admin' => $club->members()->where('role', 'admin')->count(),
                'manager' => $club->members()->where('role', 'manager')->count(),
                'treasurer' => $club->members()->where('role', 'treasurer')->count(),
                'secretary' => $club->members()->where('role', 'secretary')->count(),
                'member' => $club->members()->where('role', 'member')->count(),
            ],
            'by_status' => [
                'pending' => $club->members()->where('status', 'pending')->count(),
                'active' => $club->members()->where('status', 'active')->count(),
                'inactive' => $club->members()->where('status', 'inactive')->count(),
                'suspended' => $club->members()->where('status', 'suspended')->count(),
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

    /**
     * Thêm thành viên hoặc gửi join request
     */
    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'sometimes|in:member,admin,manager,treasurer,secretary',
            'position' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'status' => 'sometimes|in:pending,active',
        ]);

        // Kiểm tra đã là thành viên chưa
        if ($club->hasMember($validated['user_id'])) {
            return ResponseHelper::error('Người dùng đã là thành viên của CLB này', 409);
        }

        return DB::transaction(function () use ($club, $validated) {
            $member = ClubMember::create([
                'club_id' => $club->id,
                'user_id' => $validated['user_id'],
                'role' => $validated['role'] ?? 'member',
                'position' => $validated['position'] ?? null,
                'status' => $validated['status'] ?? 'active',
                'message' => $validated['message'] ?? null,
                'joined_at' => $validated['status'] === 'active' ? now() : null,
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

    /**
     * Lấy chi tiết thành viên
     */
    public function show($clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->with(['user', 'club', 'reviewer'])
            ->findOrFail($memberId);

        return ResponseHelper::success($member, 'Lấy thông tin thành viên thành công');
    }

    /**
     * Cập nhật thành viên hoặc duyệt/từ chối join request
     */
    public function update(Request $request, $clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)->findOrFail($memberId);
        $userId = auth()->id();

        // Kiểm tra quyền
        $club = $member->club;
        if (!$club->canManage($userId) && $member->user_id !== $userId) {
            return ResponseHelper::error('Không có quyền thực hiện thao tác này', 403);
        }

        $validated = $request->validate([
            'role' => 'sometimes|in:member,admin,manager,treasurer,secretary',
            'position' => 'nullable|string|max:255',
            'status' => 'sometimes|in:pending,active,inactive,suspended',
            'notes' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($member, $validated, $userId) {
            // Nếu approve join request
            if (isset($validated['status']) && $validated['status'] === 'active' && $member->status === 'pending') {
                $member->update([
                    'status' => 'active',
                    'reviewed_by' => $userId,
                    'reviewed_at' => now(),
                    'joined_at' => now(),
                    'role' => $validated['role'] ?? $member->role,
                ]);
            }
            // Nếu reject join request
            elseif (isset($validated['rejection_reason']) && $member->status === 'pending') {
                $member->update([
                    'status' => 'inactive',
                    'reviewed_by' => $userId,
                    'reviewed_at' => now(),
                    'rejection_reason' => $validated['rejection_reason'],
                ]);
            }
            // Cập nhật thông tin thường
            else {
                $member->update($validated);
            }

            $member->load(['user', 'reviewer']);

            return ResponseHelper::success($member, 'Cập nhật thành viên thành công');
        });
    }

    /**
     * Xóa thành viên
     */
    public function destroy($clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)->findOrFail($memberId);
        $userId = auth()->id();

        // Kiểm tra quyền
        $club = $member->club;
        if (!$club->canManage($userId) && $member->user_id !== $userId) {
            return ResponseHelper::error('Không có quyền xóa thành viên này', 403);
        }

        $member->delete();

        return ResponseHelper::success([], 'Xóa thành viên thành công');
    }

    /**
     * Duyệt join request
     */
    public function approve(Request $request, $clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->where('status', 'pending')
            ->findOrFail($memberId);

        $club = $member->club;
        $userId = auth()->id();

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền duyệt', 403);
        }

        $validated = $request->validate([
            'role' => 'sometimes|in:member,admin,manager,treasurer,secretary',
        ]);

        $member->update([
            'status' => 'active',
            'role' => $validated['role'] ?? 'member',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'joined_at' => now(),
        ]);

        $member->load(['user', 'reviewer']);

        return ResponseHelper::success($member, 'Yêu cầu tham gia đã được duyệt');
    }

    /**
     * Từ chối join request
     */
    public function reject(Request $request, $clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->where('status', 'pending')
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
            'status' => 'inactive',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $member->load(['user', 'reviewer']);

        return ResponseHelper::success($member, 'Yêu cầu tham gia đã bị từ chối');
    }
}
