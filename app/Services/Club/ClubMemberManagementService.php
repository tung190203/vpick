<?php

namespace App\Services\Club;

use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Enums\ClubMembershipStatus;
use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ClubMemberManagementService
{
    public function getMembers(Club $club, array $filters): LengthAwarePaginator
    {
        $query = $club->members()->with(['user' => User::FULL_RELATIONS, 'reviewer']);

        if (!empty($filters['search'])) {
            $query->whereHas('user', function ($q) use ($filters) {
                $q->where('full_name', 'LIKE', '%' . $filters['search'] . '%');
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    public function getMemberStatistics(Club $club): array
    {
        $allMembers = ClubMember::where('club_id', $club->id)->whereHas('user');
        $joined = clone $allMembers;
        $joined->where('membership_status', ClubMembershipStatus::Joined);

        return [
            'total' => (clone $joined)->where('status', ClubMemberStatus::Active)->count(),
            'by_role' => [
                'admin' => (clone $joined)->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Admin)->count(),
                'manager' => (clone $joined)->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Manager)->count(),
                'treasurer' => (clone $joined)->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Treasurer)->count(),
                'secretary' => (clone $joined)->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Secretary)->count(),
                'member' => (clone $joined)->where('status', ClubMemberStatus::Active)->where('role', ClubMemberRole::Member)->count(),
            ],
            'by_status' => [
                'pending' => (clone $allMembers)->where('status', ClubMemberStatus::Pending)->count(),
                'active' => (clone $allMembers)->where('status', ClubMemberStatus::Active)->count(),
                'inactive' => (clone $allMembers)->where('status', ClubMemberStatus::Inactive)->count(),
                'suspended' => (clone $allMembers)->where('status', ClubMemberStatus::Suspended)->count(),
            ],
            'by_membership_status' => [
                'pending' => (clone $allMembers)->where('membership_status', ClubMembershipStatus::Pending)->count(),
                'joined' => (clone $allMembers)->where('membership_status', ClubMembershipStatus::Joined)->count(),
                'rejected' => (clone $allMembers)->where('membership_status', ClubMembershipStatus::Rejected)->count(),
                'left' => (clone $allMembers)->where('membership_status', ClubMembershipStatus::Left)->count(),
                'cancelled' => (clone $allMembers)->where('membership_status', ClubMembershipStatus::Cancelled)->count(),
            ],
        ];
    }

    public function inviteMember(Club $club, array $data, int $inviterId): ClubMember
    {
        if ($club->hasMember($data['user_id'])) {
            throw new \Exception('Người dùng đã là thành viên của CLB này');
        }

        return ClubMember::create([
            'club_id' => $club->id,
            'user_id' => $data['user_id'],
            'invited_by' => $inviterId,
            'role' => $data['role'] ?? ClubMemberRole::Member,
            'position' => $data['position'] ?? null,
            'membership_status' => ClubMembershipStatus::Pending,
            'status' => ClubMemberStatus::Pending,
            'message' => $data['message'] ?? null,
            'joined_at' => null,
        ]);
    }

    public function updateMember(ClubMember $member, array $data, int $userId, Club $club): ClubMember
    {
        $isSelfUpdate = $member->user_id === $userId;
        $currentUserMember = $club->activeMembers()->where('user_id', $userId)->first();
        $currentUserRole = $currentUserMember?->role;

        return DB::transaction(function () use ($member, $data, $userId, $club, $isSelfUpdate, $currentUserRole) {
            if (isset($data['role'])) {
                $this->validateRoleUpdate($data['role'], $isSelfUpdate, $currentUserRole, $member, $club);
            }

            if (isset($data['status'])) {
                $this->validateStatusUpdate($data['status'], $isSelfUpdate, $member, $club);
            }

            if (isset($data['status']) && $data['status'] === ClubMemberStatus::Active && $member->membership_status === ClubMembershipStatus::Pending) {
                $member->update([
                    'membership_status' => ClubMembershipStatus::Joined,
                    'status' => ClubMemberStatus::Active,
                    'reviewed_by' => $userId,
                    'reviewed_at' => now(),
                    'joined_at' => now(),
                    'role' => $data['role'] ?? $member->role,
                ]);
            } elseif (isset($data['rejection_reason']) && $member->membership_status === ClubMembershipStatus::Pending) {
                $member->delete();
                throw new \Exception('DELETED');
            } else {
                $member->update($data);
            }

            return $member;
        });
    }

    public function kickMember(ClubMember $member, int $kickerId): void
    {
        if ($member->user_id === $kickerId) {
            throw new \Exception('Bạn không thể đuổi chính mình khỏi CLB. Vui lòng sử dụng chức năng Rời CLB');
        }

        if ($member->role === ClubMemberRole::Admin && !$member->club->hasAtLeastOneAdminAfterRemoving($member->id)) {
            throw new \Exception('Không thể đuổi admin này vì sẽ không còn admin nào trong CLB. Vui lòng chỉ định admin khác trước');
        }

        $member->update([
            'membership_status' => ClubMembershipStatus::Left,
            'status' => ClubMemberStatus::Suspended,
            'left_at' => now(),
        ]);
    }

    public function cancelInvitation(ClubMember $member, int $inviterId): void
    {
        if ($member->membership_status !== ClubMembershipStatus::Pending || $member->invited_by !== $inviterId) {
            throw new \Exception('Chỉ có thể hủy lời mời do chính bạn gửi');
        }

        $member->delete();
    }

    private function validateRoleUpdate(string $newRole, bool $isSelfUpdate, ?ClubMemberRole $currentUserRole, ClubMember $member, Club $club): void
    {
        $canUpdateRole = in_array($currentUserRole, [ClubMemberRole::Admin, ClubMemberRole::Secretary], true);

        if (!$canUpdateRole) {
            throw new \Exception('Chỉ admin hoặc thư ký mới có quyền thay đổi role của thành viên');
        }

        if ($currentUserRole === ClubMemberRole::Secretary && $newRole === ClubMemberRole::Admin->value) {
            throw new \Exception('Thư ký không có quyền chỉ định role Quản trị viên');
        }

        if ($isSelfUpdate) {
            throw new \Exception('Bạn không thể thay đổi role của chính mình');
        }

        $isDowngradingAdmin = $member->role === ClubMemberRole::Admin && !in_array($newRole, [ClubMemberRole::Admin->value, ClubMemberRole::Manager->value], true);
        if ($isDowngradingAdmin && !$club->hasAtLeastOneAdminAfterRemoving($member->id)) {
            throw new \Exception('Không thể thay đổi role của admin này vì sẽ không còn admin nào trong CLB');
        }
    }

    private function validateStatusUpdate(string $newStatus, bool $isSelfUpdate, ClubMember $member, Club $club): void
    {
        $isSuspending = in_array($newStatus, [ClubMemberStatus::Inactive->value, ClubMemberStatus::Suspended->value], true);

        if ($isSuspending && $member->role === ClubMemberRole::Admin) {
            if (!$club->hasAtLeastOneAdminAfterRemoving($member->id)) {
                $message = $isSelfUpdate
                    ? 'Bạn không thể tự suspend chính mình vì sẽ không còn admin nào trong CLB'
                    : 'Không thể suspend admin này vì sẽ không còn admin nào trong CLB';
                throw new \Exception($message);
            }
        }
    }
}
