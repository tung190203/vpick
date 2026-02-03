<?php

namespace App\Http\Controllers\Club;

use App\Enums\ClubMemberRole;
use App\Enums\ClubNotificationPriority;
use App\Enums\ClubNotificationStatus;
use App\Helpers\ResponseHelper;
use App\Http\Resources\Club\ClubNotificationResource;
use App\Models\Club\Club;
use App\Models\Club\ClubNotification;
use App\Models\Club\ClubNotificationType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * club_alert: list toàn bộ thông báo của CLB (khác user_notification).
 * Route: api/club/{clubId}/notifications/* — dành cho màn Thông báo trong CLB.
 */
class ClubNotificationController extends Controller
{
    public function index(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'is_pinned' => 'sometimes|boolean',
            'status' => ['sometimes', Rule::enum(ClubNotificationStatus::class)],
            'type_id' => 'sometimes|exists:club_notification_types,id',
        ]);

        $query = $club->notifications()->with(['type', 'creator', 'recipients.user']);

        $member = $userId ? $club->activeMembers()->where('user_id', $userId)->first() : null;
        $canManage = $member && in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary]);
        if (!$canManage) {
            $query->where('status', ClubNotificationStatus::Sent)
                ->whereHas('recipients', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
        }

        if (isset($validated['is_pinned'])) {
            $query->where('is_pinned', $validated['is_pinned']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['type_id'])) {
            $query->where('club_notification_type_id', $validated['type_id']);
        }

        $perPage = $validated['per_page'] ?? 15;
        $notifications = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $data = ['notifications' => ClubNotificationResource::collection($notifications)];
        $meta = [
            'current_page' => $notifications->currentPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'last_page' => $notifications->lastPage(),
        ];
        return ResponseHelper::success($data, 'Lấy danh sách thông báo thành công', 200, $meta);
    }

    public function store(Request $request, $clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        $member = $club->activeMembers()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền tạo thông báo', 403);
        }

        $validated = $request->validate([
            'club_notification_type_id' => 'required|exists:club_notification_types,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'attachment_url' => 'nullable|string',
            'priority' => ['sometimes', Rule::enum(ClubNotificationPriority::class)],
            'status' => ['sometimes', Rule::enum(ClubNotificationStatus::class)],
            'metadata' => 'nullable|array',
            'is_pinned' => 'sometimes|boolean',
            'scheduled_at' => 'nullable|date|after:now',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        return DB::transaction(function () use ($club, $validated, $userId) {
            $notification = ClubNotification::create([
                'club_id' => $club->id,
                'club_notification_type_id' => $validated['club_notification_type_id'],
                'title' => $validated['title'],
                'content' => $validated['content'],
                'attachment_url' => $validated['attachment_url'] ?? null,
                'priority' => $validated['priority'] ?? ClubNotificationPriority::Normal,
                'status' => $validated['status'] ?? ClubNotificationStatus::Draft,
                'metadata' => $validated['metadata'] ?? null,
                'is_pinned' => $validated['is_pinned'] ?? false,
                'scheduled_at' => $validated['scheduled_at'] ?? null,
                'created_by' => $userId,
            ]);

            if (!empty($validated['user_ids'])) {
                foreach ($validated['user_ids'] as $recipientUserId) {
                    $notification->recipients()->create([
                        'user_id' => $recipientUserId,
                        'is_read' => false,
                    ]);
                }
            }

            $notification->load(['type', 'creator', 'recipients.user']);
            return ResponseHelper::success(new ClubNotificationResource($notification), 'Tạo thông báo thành công', 201);
        });
    }

    public function show($clubId, $notificationId)
    {
        $notification = ClubNotification::where('club_id', $clubId)
            ->with(['type', 'creator', 'recipients.user'])
            ->findOrFail($notificationId);

        return ResponseHelper::success(new ClubNotificationResource($notification), 'Lấy thông tin thông báo thành công');
    }

    public function update(Request $request, $clubId, $notificationId)
    {
        $notification = ClubNotification::where('club_id', $clubId)->findOrFail($notificationId);
        $userId = auth()->id();

        if ($notification->created_by !== $userId) {
            $club = $notification->club;
            $member = $club->activeMembers()->where('user_id', $userId)->first();
            if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
                return ResponseHelper::error('Không có quyền cập nhật thông báo này', 403);
            }
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'attachment_url' => 'nullable|string',
            'priority' => ['sometimes', Rule::enum(ClubNotificationPriority::class)],
            'status' => ['sometimes', Rule::enum(ClubNotificationStatus::class)],
            'metadata' => 'nullable|array',
            'scheduled_at' => 'nullable|date',
        ]);

        $notification->update($validated);
        $notification->load(['type', 'creator', 'recipients.user']);
        return ResponseHelper::success(new ClubNotificationResource($notification), 'Cập nhật thông báo thành công');
    }

    public function destroy($clubId, $notificationId)
    {
        $notification = ClubNotification::where('club_id', $clubId)->findOrFail($notificationId);
        $userId = auth()->id();

        if ($notification->created_by !== $userId) {
            $club = $notification->club;
            $member = $club->activeMembers()->where('user_id', $userId)->first();
            if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
                return ResponseHelper::error('Không có quyền xóa thông báo này', 403);
            }
        }

        $notification->delete();

        return ResponseHelper::success('Xóa thông báo thành công');
    }

    public function togglePin($clubId, $notificationId)
    {
        $notification = ClubNotification::where('club_id', $clubId)->findOrFail($notificationId);
        $userId = auth()->id();

        $club = $notification->club;
        $member = $club->activeMembers()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền ghim thông báo', 403);
        }

        $notification->togglePin();

        $notification->load(['type', 'creator', 'recipients.user']);
        return ResponseHelper::success(new ClubNotificationResource($notification), 'Đã cập nhật trạng thái ghim');
    }

    public function markAsRead($clubId, $notificationId)
    {
        $notification = ClubNotification::where('club_id', $clubId)->findOrFail($notificationId);
        $userId = auth()->id();

        $recipient = $notification->recipients()->where('user_id', $userId)->first();
        if (!$recipient) {
            return ResponseHelper::error('Bạn không phải người nhận thông báo này', 403);
        }

        $recipient->markAsRead();

        return ResponseHelper::success([], 'Đã đánh dấu đọc');
    }

    public function getNotificationTypes()
    {
        $types = ClubNotificationType::where('is_active', true)->get();

        return ResponseHelper::success($types, 'Lấy danh sách loại thông báo thành công');
    }

    public function send($clubId, $notificationId)
    {
        $notification = ClubNotification::where('club_id', $clubId)->findOrFail($notificationId);
        $userId = auth()->id();

        $club = $notification->club;
        $member = $club->activeMembers()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền gửi thông báo', 403);
        }

        if ($notification->status === 'sent') {
            return ResponseHelper::error('Thông báo đã được gửi', 422);
        }

        $notification->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        if (empty($notification->recipients()->count())) {
            $allMembers = $club->activeMembers()->pluck('user_id');
            foreach ($allMembers as $memberUserId) {
                $notification->recipients()->firstOrCreate([
                    'user_id' => $memberUserId,
                ], [
                    'is_read' => false,
                ]);
            }
        }

        $notification->load(['type', 'creator', 'recipients.user']);
        return ResponseHelper::success(new ClubNotificationResource($notification), 'Gửi thông báo thành công');
    }
}
