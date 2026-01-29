<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Enums\ClubMembershipStatus;
use App\Helpers\ResponseHelper;
use App\Http\Resources\Club\ClubMemberResource;
use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use App\Models\User;
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

        $query = $club->members()->with([
            'user' => User::FULL_RELATIONS,
            'reviewer',
        ]);

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
        if (empty($validated['status'])) {
            // Mặc định chỉ hiển thị thành viên đã join (membership_status = joined)
            $query->where('membership_status', ClubMembershipStatus::Joined);
        }

        $perPage = $validated['per_page'] ?? 15;
        $members = $query->paginate($perPage);

        $joined = fn () => $club->members()->where('membership_status', ClubMembershipStatus::Joined);
        $statistics = [
            'total' => $joined()->where('status', ClubMemberStatus::Active)->count(),
            'by_role' => [
                'admin' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Admin)->count(),
                'manager' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Manager)->count(),
                'treasurer' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Treasurer)->count(),
                'secretary' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Secretary)->count(),
                'member' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Member)->count(),
            ],
            'by_status' => [
                'pending' => $club->members()->where('status', ClubMemberStatus::Pending)->count(),
                'active' => $club->members()->where('status', ClubMemberStatus::Active)->count(),
                'inactive' => $club->members()->where('status', ClubMemberStatus::Inactive)->count(),
                'suspended' => $club->members()->where('status', ClubMemberStatus::Suspended)->count(),
            ],
            'by_membership_status' => [
                'pending' => $club->members()->where('membership_status', ClubMembershipStatus::Pending)->count(),
                'joined' => $club->members()->where('membership_status', ClubMembershipStatus::Joined)->count(),
                'rejected' => $club->members()->where('membership_status', ClubMembershipStatus::Rejected)->count(),
                'left' => $club->members()->where('membership_status', ClubMembershipStatus::Left)->count(),
                'cancelled' => $club->members()->where('membership_status', ClubMembershipStatus::Cancelled)->count(),
            ],
        ];

        $data = [
            'members' => ClubMemberResource::collection($members),
            'statistics' => $statistics,
        ];
        $meta = [
            'current_page' => $members->currentPage(),
            'per_page' => $members->perPage(),
            'total' => $members->total(),
            'last_page' => $members->lastPage(),
        ];

        return ResponseHelper::success($data, 'Lấy danh sách thành viên thành công', 200, $meta);
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
        ]);

        // Check duplicate member (bất kỳ status nào)
        if ($club->hasMember($validated['user_id'])) {
            return ResponseHelper::error('Người dùng đã là thành viên của CLB này', 409);
        }

        return DB::transaction(function () use ($club, $validated) {
            // Admin thêm user = gửi lời mời; user phải đồng ý mới thành member (status Pending, invited_by = admin)
            $member = ClubMember::create([
                'club_id' => $club->id,
                'user_id' => $validated['user_id'],
                'invited_by' => auth()->id(),
                'role' => $validated['role'] ?? ClubMemberRole::Member,
                'position' => $validated['position'] ?? null,
                'membership_status' => ClubMembershipStatus::Pending,
                'status' => ClubMemberStatus::Pending,
                'message' => $validated['message'] ?? null,
                'joined_at' => null,
            ]);

            $member->load(['user' => User::FULL_RELATIONS, 'club', 'inviter', 'reviewer']);

            return ResponseHelper::success(
                new ClubMemberResource($member),
                'Đã gửi lời mời tham gia CLB, chờ user đồng ý',
                201
            );
        });
    }

    public function show($clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->with(['user' => User::FULL_RELATIONS, 'club', 'reviewer'])
            ->findOrFail($memberId);

        return ResponseHelper::success(new ClubMemberResource($member), 'Lấy thông tin thành viên thành công');
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
            if (isset($validated['status']) && $validated['status'] === ClubMemberStatus::Active && $member->membership_status === ClubMembershipStatus::Pending) {
                $member->update([
                    'membership_status' => ClubMembershipStatus::Joined,
                    'status' => ClubMemberStatus::Active,
                    'reviewed_by' => $userId,
                    'reviewed_at' => now(),
                    'joined_at' => now(),
                    'role' => $validated['role'] ?? $member->role,
                ]);
            } elseif (isset($validated['rejection_reason']) && $member->membership_status === ClubMembershipStatus::Pending) {
                // Từ chối = xóa record để user có thể gửi yêu cầu lại
                $member->delete();
                return ResponseHelper::success([], 'Đã từ chối thành viên');
            } else {
                $member->update($validated);
            }

            $member->load(['user' => User::FULL_RELATIONS, 'reviewer']);

            return ResponseHelper::success(new ClubMemberResource($member), 'Cập nhật thành viên thành công');
        });
    }

    /**
     * Admin đuổi thành viên: membership_status = left, status = suspended.
     */
    public function destroy($clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)->findOrFail($memberId);
        $userId = auth()->id();

        $club = $member->club;
        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền đuổi thành viên', 403);
        }

        if ($member->membership_status !== ClubMembershipStatus::Joined || $member->status !== ClubMemberStatus::Active) {
            return ResponseHelper::error('Chỉ có thể đuổi thành viên đang active', 400);
        }

        $member->update([
            'membership_status' => ClubMembershipStatus::Left,
            'status' => ClubMemberStatus::Suspended,
            'left_at' => now(),
        ]);

        return ResponseHelper::success([], 'Đã đuổi thành viên khỏi CLB');
    }

    public function approve(Request $request, $clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->where('membership_status', ClubMembershipStatus::Pending)
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
            'membership_status' => ClubMembershipStatus::Joined,
            'status' => ClubMemberStatus::Active,
            'role' => $validated['role'] ?? ClubMemberRole::Member,
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'joined_at' => now(),
        ]);

        $member->load(['user' => User::FULL_RELATIONS, 'reviewer']);

        return ResponseHelper::success(new ClubMemberResource($member), 'Yêu cầu tham gia đã được duyệt');
    }

    public function reject(Request $request, $clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->where('membership_status', ClubMembershipStatus::Pending)
            ->findOrFail($memberId);

        $club = $member->club;
        $userId = auth()->id();

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền từ chối', 403);
        }

        $validated = $request->validate(['rejection_reason' => 'required|string']);

        // Từ chối: membership_status = rejected, status = inactive để thống kê by_status.pending không đếm request đã từ chối
        $member->update([
            'membership_status' => ClubMembershipStatus::Rejected,
            'status' => ClubMemberStatus::Inactive,
            'rejection_reason' => $validated['rejection_reason'],
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
        ]);

        return ResponseHelper::success([], 'Yêu cầu tham gia đã bị từ chối');
    }

    public function statistics($clubId)
    {
        $club = Club::findOrFail($clubId);
        $joined = fn () => $club->members()->where('membership_status', ClubMembershipStatus::Joined);

        $statistics = [
            'total' => $joined()->where('status', ClubMemberStatus::Active)->count(),
            'by_role' => [
                'admin' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Admin)->count(),
                'manager' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Manager)->count(),
                'treasurer' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Treasurer)->count(),
                'secretary' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Secretary)->count(),
                'member' => $joined()->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Member)->count(),
            ],
            'by_status' => [
                'pending' => $club->members()->where('status', ClubMemberStatus::Pending)->count(),
                'active' => $club->members()->where('status', ClubMemberStatus::Active)->count(),
                'inactive' => $club->members()->where('status', ClubMemberStatus::Inactive)->count(),
                'suspended' => $club->members()->where('status', ClubMemberStatus::Suspended)->count(),
            ],
            'by_membership_status' => [
                'pending' => $club->members()->where('membership_status', ClubMembershipStatus::Pending)->count(),
                'joined' => $club->members()->where('membership_status', ClubMembershipStatus::Joined)->count(),
                'rejected' => $club->members()->where('membership_status', ClubMembershipStatus::Rejected)->count(),
                'left' => $club->members()->where('membership_status', ClubMembershipStatus::Left)->count(),
                'cancelled' => $club->members()->where('membership_status', ClubMembershipStatus::Cancelled)->count(),
            ],
        ];

        return ResponseHelper::success($statistics, 'Lấy thống kê thành viên thành công');
    }
}
