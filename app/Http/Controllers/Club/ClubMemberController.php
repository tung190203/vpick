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
        // Note: $club->members() đã filter membership_status = Joined, không cần thêm điều kiện này

        $perPage = $validated['per_page'] ?? 15;
        $members = $query->paginate($perPage);

        // Query tất cả members để tính statistics (không chỉ Joined)
        $allMembers = fn () => ClubMember::where('club_id', $club->id)->whereHas('user');
        // Query chỉ members đã joined và active cho total và by_role
        $joined = fn () => $allMembers()->where('membership_status', ClubMembershipStatus::Joined);

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
                'pending' => $allMembers()->where('status', ClubMemberStatus::Pending)->count(),
                'active' => $allMembers()->where('status', ClubMemberStatus::Active)->count(),
                'inactive' => $allMembers()->where('status', ClubMemberStatus::Inactive)->count(),
                'suspended' => $allMembers()->where('status', ClubMemberStatus::Suspended)->count(),
            ],
            'by_membership_status' => [
                'pending' => $allMembers()->where('membership_status', ClubMembershipStatus::Pending)->count(),
                'joined' => $allMembers()->where('membership_status', ClubMembershipStatus::Joined)->count(),
                'rejected' => $allMembers()->where('membership_status', ClubMembershipStatus::Rejected)->count(),
                'left' => $allMembers()->where('membership_status', ClubMembershipStatus::Left)->count(),
                'cancelled' => $allMembers()->where('membership_status', ClubMembershipStatus::Cancelled)->count(),
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

    /**
     * Admin/manager CLB gửi lời mời user tham gia CLB.
     * Bắt buộc truyền user_id (id người được mời) trong body.
     * User được mời sẽ thấy lời mời tại GET /api/clubs/my-invitations và đồng ý/từ chối qua invitations/accept hoặc reject.
     */
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
        ], [
            'user_id.required' => 'Vui lòng truyền user_id (id người được mời tham gia CLB).',
            'user_id.exists' => 'Người dùng không tồn tại.',
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
        $isSelfUpdate = $member->user_id === $userId;
        $canManage = $club->canManage($userId);

        // Lấy role của user hiện tại trong club
        $currentUserMember = $club->activeMembers()->where('user_id', $userId)->first();
        $currentUserRole = $currentUserMember ? $currentUserMember->role : null;
        $canUpdateRole = in_array($currentUserRole, [ClubMemberRole::Admin, ClubMemberRole::Secretary], true);

        if (!$canManage && !$isSelfUpdate) {
            return ResponseHelper::error('Không có quyền thực hiện thao tác này', 403);
        }

        $validated = $request->validate([
            'role' => ['sometimes', Rule::enum(ClubMemberRole::class)],
            'position' => 'nullable|string|max:255',
            'status' => ['sometimes', Rule::enum(ClubMemberStatus::class)],
            'notes' => 'nullable|string',
            'rejection_reason' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($member, $validated, $userId, $club, $isSelfUpdate, $canManage, $canUpdateRole, $currentUserRole) {
            // Validation: Chỉ admin hoặc secretary mới được update role
            if (isset($validated['role'])) {
                if (!$canUpdateRole) {
                    return ResponseHelper::error('Chỉ admin hoặc thư ký mới có quyền thay đổi role của thành viên', 403);
                }

                // Validation: Secretary không được set role = Admin
                if ($currentUserRole === ClubMemberRole::Secretary && $validated['role'] === ClubMemberRole::Admin) {
                    return ResponseHelper::error('Thư ký không có quyền chỉ định role Quản trị viên', 403);
                }
            }

            // Validation: Không cho phép tự thay đổi role của chính mình
            if (isset($validated['role']) && $isSelfUpdate) {
                return ResponseHelper::error('Bạn không thể thay đổi role của chính mình', 403);
            }

            // Validation: Không cho phép tự suspend chính mình nếu là admin duy nhất
            if (isset($validated['status']) && $isSelfUpdate) {
                $newStatus = $validated['status'];
                $isSuspendingSelf = in_array($newStatus, [ClubMemberStatus::Inactive, ClubMemberStatus::Suspended], true);

                if ($isSuspendingSelf && $member->role === ClubMemberRole::Admin && !$club->hasAtLeastOneAdminAfterRemoving($member->id)) {
                    return ResponseHelper::error('Bạn không thể tự suspend chính mình vì sẽ không còn admin nào trong CLB', 400);
                }
            }

            // Validation: Admin/Manager không thể downgrade role của admin khác thành non-admin nếu đó là admin duy nhất
            if (isset($validated['role']) && $canManage && $member->role === ClubMemberRole::Admin) {
                $newRole = $validated['role'];
                $isDowngradingAdmin = !in_array($newRole, [ClubMemberRole::Admin, ClubMemberRole::Manager], true);

                if ($isDowngradingAdmin && !$club->hasAtLeastOneAdminAfterRemoving($member->id)) {
                    return ResponseHelper::error('Không thể thay đổi role của admin này vì sẽ không còn admin nào trong CLB', 400);
                }
            }

            // Validation: Không cho phép suspend admin nếu đó là admin duy nhất
            if (isset($validated['status']) && $canManage) {
                $newStatus = $validated['status'];
                $isSuspending = in_array($newStatus, [ClubMemberStatus::Inactive, ClubMemberStatus::Suspended], true);

                if ($isSuspending && $member->role === ClubMemberRole::Admin && !$club->hasAtLeastOneAdminAfterRemoving($member->id)) {
                    return ResponseHelper::error('Không thể suspend admin này vì sẽ không còn admin nào trong CLB', 400);
                }
            }

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
     * Admin: (1) Hủy lời mời đã gửi (pending, invited_by = mình) → xóa record.
     *       (2) Đuổi thành viên đang tham gia (joined + active) → membership_status = left, status = suspended.
     */
    public function destroy($clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)->findOrFail($memberId);
        $userId = auth()->id();

        $club = $member->club;
        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền thực hiện', 403);
        }

        // Lời mời do admin gửi (pending, invited_by = mình) → hủy lời mời = xóa record
        if (
            $member->membership_status === ClubMembershipStatus::Pending
            && $member->invited_by === $userId
        ) {
            $member->delete();
            return ResponseHelper::success([], 'Đã hủy lời mời tham gia CLB');
        }

        // Thành viên đang tham gia → đuổi (left + suspended)
        if ($member->membership_status !== ClubMembershipStatus::Joined || $member->status !== ClubMemberStatus::Active) {
            return ResponseHelper::error('Chỉ có thể đuổi thành viên đang tham gia hoặc hủy lời mời do chính bạn gửi', 400);
        }

        // Validation: Không cho phép kick chính mình
        if ($member->user_id === $userId) {
            return ResponseHelper::error('Bạn không thể đuổi chính mình khỏi CLB. Vui lòng sử dụng chức năng Rời CLB', 400);
        }

        // Validation: Đảm bảo có ít nhất 1 admin còn lại sau khi kick member này
        if ($member->role === ClubMemberRole::Admin && !$club->hasAtLeastOneAdminAfterRemoving($member->id)) {
            return ResponseHelper::error('Không thể đuổi admin này vì sẽ không còn admin nào trong CLB. Vui lòng chỉ định admin khác trước', 400);
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
        // Query tất cả members để tính statistics (không chỉ Joined)
        $allMembers = fn () => ClubMember::where('club_id', $club->id)->whereHas('user');
        // Query chỉ members đã joined và active cho total và by_role
        $joined = fn () => $allMembers()->where('membership_status', ClubMembershipStatus::Joined);

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
                'pending' => $allMembers()->where('status', ClubMemberStatus::Pending)->count(),
                'active' => $allMembers()->where('status', ClubMemberStatus::Active)->count(),
                'inactive' => $allMembers()->where('status', ClubMemberStatus::Inactive)->count(),
                'suspended' => $allMembers()->where('status', ClubMemberStatus::Suspended)->count(),
            ],
            'by_membership_status' => [
                'pending' => $allMembers()->where('membership_status', ClubMembershipStatus::Pending)->count(),
                'joined' => $allMembers()->where('membership_status', ClubMembershipStatus::Joined)->count(),
                'rejected' => $allMembers()->where('membership_status', ClubMembershipStatus::Rejected)->count(),
                'left' => $allMembers()->where('membership_status', ClubMembershipStatus::Left)->count(),
                'cancelled' => $allMembers()->where('membership_status', ClubMembershipStatus::Cancelled)->count(),
            ],
        ];

        return ResponseHelper::success($statistics, 'Lấy thống kê thành viên thành công');
    }
}
