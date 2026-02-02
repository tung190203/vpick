<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Enums\ClubMembershipStatus;
use App\Helpers\ResponseHelper;
use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Resources\Club\ClubMemberResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ClubJoinRequestController extends Controller
{
    /**
     * Lấy danh sách yêu cầu tham gia CLB
     * Mặc định chỉ lấy pending, có thể filter theo status
     */
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
            'status' => 'sometimes|in:pending,approved,rejected',
        ]);

        // Mặc định chỉ lấy pending = yêu cầu tham gia từ user (invited_by null), không bao gồm lời mời từ admin
        $status = $validated['status'] ?? 'pending';

        $query = $club->members()
            ->with([
                'user' => User::FULL_RELATIONS,
                'reviewer',
                'inviter',
            ]);

        // Chỉ hiển thị yêu cầu từ user (invited_by null). Lời mời từ admin nằm ở members list với status Pending
        $query->whereNull('invited_by');

        if ($status === 'pending') {
            $query->where('membership_status', ClubMembershipStatus::Pending);
        } elseif ($status === 'approved') {
            $query->where('membership_status', ClubMembershipStatus::Joined)->whereNotNull('reviewed_at');
        } elseif ($status === 'rejected') {
            $query->where('membership_status', ClubMembershipStatus::Rejected)->whereNotNull('rejection_reason');
        }

        $perPage = $validated['per_page'] ?? 15;
        $requests = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $meta = [
            'current_page' => $requests->currentPage(),
            'per_page' => $requests->perPage(),
            'total' => $requests->total(),
            'last_page' => $requests->lastPage(),
        ];

        return ResponseHelper::success(
            ClubMemberResource::collection($requests),
            'Lấy danh sách yêu cầu tham gia thành công',
            200,
            $meta
        );
    }

    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập để gửi yêu cầu tham gia', 401);
        }

        // Chỉ cho gửi khi chưa joined và chưa có request pending (rejected/left được gửi lại)
        if (!$club->canSendJoinRequest($userId)) {
            if ($club->hasMember($userId)) {
                return ResponseHelper::error('Bạn đã là thành viên của CLB này', 409);
            }
            if ($club->hasPendingRequest($userId)) {
                return ResponseHelper::error('Bạn đã gửi yêu cầu tham gia. Vui lòng chờ duyệt', 409);
            }
            return ResponseHelper::error('Bạn không thể gửi yêu cầu tham gia', 409);
        }

        $validated = $request->validate([
            'message' => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($club, $userId, $validated) {
            $existing = ClubMember::where('club_id', $club->id)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($existing && in_array($existing->membership_status, [ClubMembershipStatus::Rejected, ClubMembershipStatus::Left, ClubMembershipStatus::Cancelled], true)) {
                // Join lại: cập nhật record cũ thành pending
                $existing->update([
                    'membership_status' => ClubMembershipStatus::Pending,
                    'status' => ClubMemberStatus::Pending,
                    'message' => $validated['message'] ?? null,
                    'invited_by' => null,
                    'left_at' => null,
                    'rejection_reason' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                ]);
                $member = $existing->fresh(['user' => User::FULL_RELATIONS, 'club']);
            } else {
                $member = ClubMember::create([
                    'club_id' => $club->id,
                    'user_id' => $userId,
                    'role' => ClubMemberRole::Member,
                    'membership_status' => ClubMembershipStatus::Pending,
                    'status' => ClubMemberStatus::Pending,
                    'message' => $validated['message'] ?? null,
                ]);
                $member->load(['user' => User::FULL_RELATIONS, 'club']);
            }

            return ResponseHelper::success(
                new ClubMemberResource($member),
                'Yêu cầu tham gia đã được gửi',
                201
            );
        });
    }

    /**
     * Lấy chi tiết một yêu cầu tham gia
     */
    public function show($clubId, $requestId)
    {
        $member = ClubMember::where('club_id', $clubId)
            ->with(['user' => User::FULL_RELATIONS, 'club', 'reviewer', 'inviter'])
            ->findOrFail($requestId);

        return ResponseHelper::success(
            new ClubMemberResource($member),
            'Lấy chi tiết yêu cầu thành công'
        );
    }

    /**
     * User hủy yêu cầu tham gia của chính mình (chỉ truyền club_id)
     * DELETE /api/clubs/{clubId}/join-requests
     */
    public function destroyMyRequest($clubId)
    {
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        // Chỉ hủy được khi là yêu cầu do user tự gửi (invited_by null). Lời mời từ admin dùng reject.
        $member = ClubMember::where('club_id', $clubId)
            ->where('user_id', $userId)
            ->where('membership_status', ClubMembershipStatus::Pending)
            ->whereNull('invited_by')
            ->first();

        if (!$member) {
            return ResponseHelper::error('Không tìm thấy yêu cầu tham gia nào của bạn', 404);
        }

        // Cập nhật thành cancelled thay vì delete: giữ record (unique user_id+club_id), thống kê đúng, user gửi lại được
        $member->update([
            'membership_status' => ClubMembershipStatus::Cancelled,
            'status' => ClubMemberStatus::Inactive,
            'message' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);

        return ResponseHelper::success('Yêu cầu đã được hủy');
    }

    /**
     * Duyệt yêu cầu tham gia
     * Có thể dùng user_id (ưu tiên) hoặc requestId (backward compatible)
     */
    public function approve(Request $request, $clubId, $requestId = null)
    {
        $club = Club::findOrFail($clubId);
        $reviewerId = auth()->id();

        if (!$club->canManage($reviewerId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền duyệt', 403);
        }

        $validated = $request->validate([
            'user_id' => 'sometimes|integer|exists:users,id',
            'role' => ['sometimes', Rule::enum(ClubMemberRole::class)],
        ]);

        // Ưu tiên: Nếu có user_id trong body, approve pending request của user đó
        if (!empty($validated['user_id'])) {
            $member = ClubMember::where('club_id', $clubId)
                ->where('user_id', $validated['user_id'])
                ->where('membership_status', ClubMembershipStatus::Pending)
                ->first();

            if (!$member) {
                return ResponseHelper::error('Không tìm thấy yêu cầu tham gia nào của user này', 404);
            }
        } else {
            // Fallback: Dùng requestId từ URL (backward compatible)
            if (!$requestId) {
                return ResponseHelper::error('Cần cung cấp user_id trong body hoặc requestId trong URL', 400);
            }

            $member = ClubMember::where('club_id', $clubId)
                ->where('membership_status', ClubMembershipStatus::Pending)
                ->findOrFail($requestId);
        }

        // Chỉ duyệt được yêu cầu tham gia do USER tự gửi (invited_by = null).
        // Lời mời từ admin (invited_by != null) chỉ người được mời mới đồng ý/từ chối qua invitations/accept hoặc invitations/reject.
        if ($member->invited_by !== null) {
            return ResponseHelper::error(
                'Đây là lời mời từ admin, chỉ người được mời mới có thể đồng ý hoặc từ chối qua mục Lời mời của tôi.',
                403
            );
        }

        $member->update([
            'membership_status' => ClubMembershipStatus::Joined,
            'status' => ClubMemberStatus::Active,
            'role' => $validated['role'] ?? ClubMemberRole::Member,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'joined_at' => now(),
        ]);

        $member->load(['user' => User::FULL_RELATIONS, 'reviewer']);

        return ResponseHelper::success(
            new ClubMemberResource($member),
            'Yêu cầu đã được duyệt'
        );
    }

    /**
     * Từ chối yêu cầu tham gia
     * Có thể dùng user_id (ưu tiên) hoặc requestId (backward compatible)
     */
    public function reject(Request $request, $clubId, $requestId = null)
    {
        $club = Club::findOrFail($clubId);
        $reviewerId = auth()->id();

        if (!$club->canManage($reviewerId)) {
            return ResponseHelper::error('Chỉ admin/manager mới có quyền từ chối', 403);
        }

        $validated = $request->validate([
            'user_id' => 'sometimes|integer|exists:users,id',
            'rejection_reason' => 'required|string|max:500',
        ]);

        if (!empty($validated['user_id'])) {
            $member = ClubMember::where('club_id', $clubId)
                ->where('user_id', $validated['user_id'])
                ->where('membership_status', ClubMembershipStatus::Pending)
                ->first();

            if (!$member) {
                return ResponseHelper::error('Không tìm thấy yêu cầu tham gia nào của user này', 404);
            }
        } else {
            if (!$requestId) {
                return ResponseHelper::error('Cần cung cấp user_id trong body hoặc requestId trong URL', 400);
            }

            $member = ClubMember::where('club_id', $clubId)
                ->where('membership_status', ClubMembershipStatus::Pending)
                ->findOrFail($requestId);
        }

        // Chỉ từ chối được yêu cầu tham gia do USER tự gửi (invited_by = null).
        // Lời mời từ admin (invited_by != null) chỉ người được mời mới từ chối qua invitations/reject.
        if ($member->invited_by !== null) {
            return ResponseHelper::error(
                'Đây là lời mời từ admin, chỉ người được mời mới có thể đồng ý hoặc từ chối qua mục Lời mời của tôi.',
                403
            );
        }

        // Từ chối: membership_status = rejected, status = inactive để thống kê by_status.pending không đếm request đã từ chối
        $member->update([
            'membership_status' => ClubMembershipStatus::Rejected,
            'status' => ClubMemberStatus::Inactive,
            'rejection_reason' => $validated['rejection_reason'],
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        return ResponseHelper::success([], 'Yêu cầu đã bị từ chối');
    }

    /**
     * User: Danh sách lời mời tham gia CLB (admin mời tôi, chờ tôi đồng ý)
     * GET /api/clubs/my-invitations
     */
    public function myInvitations(Request $request)
    {
        $userId = auth()->id();
        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        $invitations = ClubMember::where('user_id', $userId)
            ->where('membership_status', ClubMembershipStatus::Pending)
            ->whereNotNull('invited_by')
            ->with(['user' => User::FULL_RELATIONS, 'club', 'inviter'])
            ->orderBy('created_at', 'desc')
            ->get();

        return ResponseHelper::success(
            ClubMemberResource::collection($invitations),
            'Lấy danh sách lời mời tham gia CLB thành công'
        );
    }

    public function acceptInvitation($clubId)
    {
        $userId = auth()->id();
        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        if ($clubId === '' || $clubId === null || !is_numeric($clubId) || (int) $clubId < 1) {
            return ResponseHelper::error('Thiếu hoặc sai clubId. Vui lòng truyền ID CLB trong URL: POST /api/clubs/{clubId}/invitations/accept', 400);
        }
        $clubId = (int) $clubId;

        $member = ClubMember::where('club_id', $clubId)
            ->where('user_id', $userId)
            ->where('membership_status', ClubMembershipStatus::Pending)
            ->whereNotNull('invited_by')
            ->first();

        if (!$member) {
            return ResponseHelper::error('Không tìm thấy lời mời tham gia CLB này', 404);
        }

        $member->update([
            'membership_status' => ClubMembershipStatus::Joined,
            'status' => ClubMemberStatus::Active,
            'joined_at' => now(),
            'reviewed_at' => now(),
        ]);

        $member->load(['user' => User::FULL_RELATIONS, 'club', 'inviter']);

        return ResponseHelper::success(
            new ClubMemberResource($member),
            'Bạn đã tham gia CLB thành công'
        );
    }
    public function rejectInvitation(Request $request, $clubId)
    {
        $userId = auth()->id();
        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        if ($clubId === '' || $clubId === null || !is_numeric($clubId) || (int) $clubId < 1) {
            return ResponseHelper::error('Thiếu hoặc sai clubId. Vui lòng truyền ID CLB trong URL: POST /api/clubs/{clubId}/invitations/reject', 400);
        }
        $clubId = (int) $clubId;

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $member = ClubMember::where('club_id', $clubId)
            ->where('user_id', $userId)
            ->where('membership_status', ClubMembershipStatus::Pending)
            ->whereNotNull('invited_by')
            ->first();

        if (!$member) {
            return ResponseHelper::error('Không tìm thấy lời mời tham gia CLB này', 404);
        }

        // Từ chối lời mời = xóa record để có thể được mời lại
        $member->delete();

        return ResponseHelper::success('Đã từ chối lời mời tham gia CLB');
    }
}
