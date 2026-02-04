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
use Illuminate\Support\Facades\Storage;
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

        // Convert string 'true'/'false' sang boolean cho query params
        if ($request->has('is_pinned')) {
            $request->merge([
                'is_pinned' => filter_var($request->input('is_pinned'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
        }

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

        // Convert string 'true'/'false' sang boolean cho form-data
        if ($request->has('is_pinned')) {
            $request->merge([
                'is_pinned' => filter_var($request->input('is_pinned'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
        }

        $validated = $request->validate([
            'club_notification_type_id' => 'required|exists:club_notification_types,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'attachment_url' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240',
            'priority' => ['sometimes', Rule::enum(ClubNotificationPriority::class)],
            'status' => ['sometimes', Rule::enum(ClubNotificationStatus::class)],
            'metadata' => 'nullable|array',
            'is_pinned' => 'sometimes|boolean',
            'scheduled_at' => 'nullable|date|after:now',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $attachmentUrl = $validated['attachment_url'] ?? null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('club_notifications/attachments', 'public');
            $attachmentUrl = Storage::url($path);
        }

        return DB::transaction(function () use ($club, $validated, $userId, $attachmentUrl) {
            $notification = ClubNotification::create([
                'club_id' => $club->id,
                'club_notification_type_id' => $validated['club_notification_type_id'],
                'title' => $validated['title'],
                'content' => $validated['content'],
                'attachment_url' => $attachmentUrl,
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
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        $notification = ClubNotification::where('club_id', $clubId)
            ->with(['type', 'creator', 'recipients.user'])
            ->findOrFail($notificationId);

        // Check permission: admin/manager/secretary xem được tất cả, member thường chỉ xem được notification đã sent và có trong recipients
        $member = $userId ? $club->activeMembers()->where('user_id', $userId)->first() : null;
        $canManage = $member && in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary]);

        if (!$canManage) {
            // Member thường chỉ xem được notification đã sent và là recipient
            if ($notification->status !== ClubNotificationStatus::Sent) {
                return ResponseHelper::error('Bạn không có quyền xem thông báo này', 403);
            }

            $isRecipient = $notification->recipients()->where('user_id', $userId)->exists();
            if (!$isRecipient) {
                return ResponseHelper::error('Bạn không có quyền xem thông báo này', 403);
            }
        }

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
            'attachment' => 'nullable|file|max:10240',
            'priority' => ['sometimes', Rule::enum(ClubNotificationPriority::class)],
            'status' => ['sometimes', Rule::enum(ClubNotificationStatus::class)],
            'metadata' => 'nullable|array',
            'scheduled_at' => 'nullable|date',
        ]);

        if ($request->hasFile('attachment')) {
            // Xóa file cũ nếu có
            if ($notification->attachment_url) {
                $oldPath = str_replace('/storage/', '', $notification->attachment_url);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('attachment')->store('club_notifications/attachments', 'public');
            $validated['attachment_url'] = Storage::url($path);
        }
        unset($validated['attachment']);

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

        // Xóa file attachment nếu có
        if ($notification->attachment_url) {
            $oldPath = str_replace('/storage/', '', $notification->attachment_url);
            Storage::disk('public')->delete($oldPath);
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
        $club = Club::findOrFail($clubId);
        $notification = ClubNotification::where('club_id', $clubId)->findOrFail($notificationId);
        $userId = auth()->id();

        $member = $userId ? $club->activeMembers()->where('user_id', $userId)->first() : null;
        $canManage = $member && in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary]);

        $recipient = $notification->recipients()->where('user_id', $userId)->first();

        if (!$recipient) {
            if (!$canManage) {
                return ResponseHelper::error('Bạn không có quyền đánh dấu đọc thông báo này', 403);
            }

            $recipient = $notification->recipients()->create([
                'user_id' => $userId,
                'is_read' => true,
                'read_at' => now(),
            ]);
        } else {
            $recipient->markAsRead();
        }

        return ResponseHelper::success([], 'Đã đánh dấu đọc');
    }

    public function markAllAsRead($clubId)
    {
        $club = Club::findOrFail($clubId);
        $userId = auth()->id();

        if (!$userId) {
            return ResponseHelper::error('Bạn cần đăng nhập', 401);
        }

        $member = $club->activeMembers()->where('user_id', $userId)->first();
        $canManage = $member && in_array($member->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Secretary]);

        return DB::transaction(function () use ($club, $userId, $canManage) {
            if ($canManage) {
                $notifications = $club->notifications()->get();

                foreach ($notifications as $notification) {
                    $recipient = $notification->recipients()->where('user_id', $userId)->first();

                    if (!$recipient) {
                        $notification->recipients()->create([
                            'user_id' => $userId,
                            'is_read' => true,
                            'read_at' => now(),
                        ]);
                    } else {
                        $recipient->markAsRead();
                    }
                }

                $message = 'Đã đánh dấu đọc tất cả thông báo';
            } else {
                $recipients = DB::table('club_notification_recipients')
                    ->join('club_notifications', 'club_notification_recipients.club_notification_id', '=', 'club_notifications.id')
                    ->where('club_notifications.club_id', $club->id)
                    ->where('club_notification_recipients.user_id', $userId)
                    ->where('club_notification_recipients.is_read', false)
                    ->update([
                        'club_notification_recipients.is_read' => true,
                        'club_notification_recipients.read_at' => now(),
                    ]);

                $message = $recipients > 0
                    ? "Đã đánh dấu đọc {$recipients} thông báo"
                    : 'Không có thông báo chưa đọc';
            }

            return ResponseHelper::success([], $message);
        });
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

        if ($notification->status === ClubNotificationStatus::Sent) {
            return ResponseHelper::error('Thông báo đã được gửi', 422);
        }

        $notification->update([
            'status' => ClubNotificationStatus::Sent,
            'sent_at' => now(),
        ]);

        if ($notification->recipients()->count() === 0) {
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
