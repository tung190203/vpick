<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Helpers\ResponseHelper;
use App\Models\Club\Club;
use App\Models\Club\ClubMember;
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
                'user.sports.scores',
                'user.sports.sport',
                'user.vnduprScores',
                'reviewer',
                'inviter',
            ]);

        // Chỉ hiển thị yêu cầu từ user (invited_by null). Lời mời từ admin (invited_by not null) nằm ở members list với status Pending
        $query->whereNull('invited_by');

        if ($status === 'pending') {
            $query->where('status', ClubMemberStatus::Pending);
        } elseif ($status === 'approved') {
            $query->where('status', ClubMemberStatus::Active)
                  ->whereNotNull('reviewed_at');
        } elseif ($status === 'rejected') {
            $query->where('status', ClubMemberStatus::Inactive)
                  ->whereNotNull('rejection_reason');
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

            $member->load(['user.vnduprScores', 'club']);

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
            ->with(['user.vnduprScores', 'club', 'reviewer', 'inviter'])
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
            ->where('status', ClubMemberStatus::Pending)
            ->whereNull('invited_by')
            ->first();

        if (!$member) {
            return ResponseHelper::error('Không tìm thấy yêu cầu tham gia nào của bạn', 404);
        }

        $member->delete();

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
                ->where('status', ClubMemberStatus::Pending)
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
                ->where('status', ClubMemberStatus::Pending)
                ->findOrFail($requestId);
        }

        $member->update([
            'status' => ClubMemberStatus::Active,
            'role' => $validated['role'] ?? ClubMemberRole::Member,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'joined_at' => now(),
        ]);

        $member->load(['user.vnduprScores', 'reviewer']);

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
                ->where('status', ClubMemberStatus::Pending)
                ->first();

            if (!$member) {
                return ResponseHelper::error('Không tìm thấy yêu cầu tham gia nào của user này', 404);
            }
        } else {
            if (!$requestId) {
                return ResponseHelper::error('Cần cung cấp user_id trong body hoặc requestId trong URL', 400);
            }

            $member = ClubMember::where('club_id', $clubId)
                ->where('status', ClubMemberStatus::Pending)
                ->findOrFail($requestId);
        }

        $member->update([
            'status' => ClubMemberStatus::Inactive,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $member->load(['user.vnduprScores', 'reviewer']);

        return ResponseHelper::success(
            new ClubMemberResource($member),
            'Yêu cầu đã bị từ chối'
        );
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
            ->where('status', ClubMemberStatus::Pending)
            ->whereNotNull('invited_by')
            ->with(['club', 'inviter'])
            ->orderBy('created_at', 'desc')
            ->get();

        return ResponseHelper::success(
            ClubMemberResource::collection($invitations),
            'Lấy danh sách lời mời tham gia CLB thành công'
        );
    }

    /**
     * User: Đồng ý lời mời tham gia CLB (admin đã mời)
     * POST /api/clubs/{clubId}/invitations/accept
     */
    public function acceptInvitation($clubId)
    {
        $userId = auth()->id();
        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        $member = ClubMember::where('club_id', $clubId)
            ->where('user_id', $userId)
            ->where('status', ClubMemberStatus::Pending)
            ->whereNotNull('invited_by')
            ->first();

        if (!$member) {
            return ResponseHelper::error('Không tìm thấy lời mời tham gia CLB này', 404);
        }

        $member->update([
            'status' => ClubMemberStatus::Active,
            'joined_at' => now(),
            'reviewed_at' => now(),
        ]);

        $member->load(['user.sports.scores', 'user.sports.sport', 'club', 'inviter']);

        return ResponseHelper::success(
            new ClubMemberResource($member),
            'Bạn đã tham gia CLB thành công'
        );
    }

    /**
     * User: Từ chối lời mời tham gia CLB
     * POST /api/clubs/{clubId}/invitations/reject
     */
    public function rejectInvitation(Request $request, $clubId)
    {
        $userId = auth()->id();
        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $member = ClubMember::where('club_id', $clubId)
            ->where('user_id', $userId)
            ->where('status', ClubMemberStatus::Pending)
            ->whereNotNull('invited_by')
            ->first();

        if (!$member) {
            return ResponseHelper::error('Không tìm thấy lời mời tham gia CLB này', 404);
        }

        $member->update([
            'status' => ClubMemberStatus::Inactive,
            'reviewed_at' => now(),
            'rejection_reason' => $validated['reason'] ?? 'User từ chối lời mời',
        ]);

        return ResponseHelper::success('Đã từ chối lời mời tham gia CLB');
    }
}
