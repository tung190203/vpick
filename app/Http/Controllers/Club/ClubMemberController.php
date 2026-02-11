<?php

namespace App\Http\Controllers\Club;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Club\GetMembersRequest;
use App\Http\Requests\Club\InviteMemberRequest;
use App\Http\Requests\Club\UpdateMemberRequest;
use App\Http\Resources\Club\ClubMemberResource;
use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use App\Models\User;
use App\Services\Club\ClubMemberManagementService;

class ClubMemberController extends Controller
{
    public function __construct(
        protected ClubMemberManagementService $memberManagementService
    ) {
    }

    public function index(GetMembersRequest $request, $clubId)
    {
        $club = Club::findOrFail($clubId);

        $members = $this->memberManagementService->getMembers($club, $request->validated());
        $statistics = $this->memberManagementService->getMemberStatistics($club);

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

    public function store(InviteMemberRequest $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền thêm thành viên', 403);
        }

        try {
            $member = $this->memberManagementService->inviteMember($club, $request->validated(), $userId);
            $member->load(['user' => User::FULL_RELATIONS, 'club', 'inviter', 'reviewer']);

            return ResponseHelper::success(
                new ClubMemberResource($member),
                'Đã gửi lời mời tham gia CLB, chờ user đồng ý',
                201
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 409);
        }
    }

    public function show($clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->with(['user' => User::FULL_RELATIONS, 'club', 'reviewer'])
            ->findOrFail($memberId);

        return ResponseHelper::success(new ClubMemberResource($member), 'Lấy thông tin thành viên thành công');
    }

    public function update(UpdateMemberRequest $request, $clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)->findOrFail($memberId);
        $userId = auth()->id();
        $club = $member->club;

        $isSelfUpdate = $member->user_id === $userId;
        $canManage = $club->canManage($userId);

        if (!$canManage && !$isSelfUpdate) {
            return ResponseHelper::error('Không có quyền thực hiện thao tác này', 403);
        }

        try {
            $member = $this->memberManagementService->updateMember($member, $request->validated(), $userId, $club);
            $member->load(['user' => User::FULL_RELATIONS, 'reviewer']);

            return ResponseHelper::success(new ClubMemberResource($member), 'Cập nhật thành viên thành công');
        } catch (\Exception $e) {
            if ($e->getMessage() === 'DELETED') {
                return ResponseHelper::success([], 'Đã từ chối thành viên');
            }
            return ResponseHelper::error($e->getMessage(), 403);
        }
    }

    public function destroy($clubId, $memberId)
    {
        $member = ClubMember::where('club_id', $clubId)->findOrFail($memberId);
        $userId = auth()->id();
        $club = $member->club;

        if (!$club->canManage($userId)) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền thực hiện', 403);
        }

        try {
            if ($member->membership_status === \App\Enums\ClubMembershipStatus::Pending && $member->invited_by === $userId) {
                $this->memberManagementService->cancelInvitation($member, $userId);
                return ResponseHelper::success([], 'Đã hủy lời mời tham gia CLB');
            }

            if ($member->membership_status !== \App\Enums\ClubMembershipStatus::Joined || $member->status !== \App\Enums\ClubMemberStatus::Active) {
                return ResponseHelper::error('Chỉ có thể đuổi thành viên đang tham gia hoặc hủy lời mời do chính bạn gửi', 400);
            }

            $this->memberManagementService->kickMember($member, $userId);
            return ResponseHelper::success([], 'Đã đuổi thành viên khỏi CLB');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 400);
        }
    }

    public function statistics($clubId)
    {
        $club = Club::findOrFail($clubId);
        $statistics = $this->memberManagementService->getMemberStatistics($club);

        return ResponseHelper::success($statistics, 'Lấy thống kê thành viên thành công');
    }
}
