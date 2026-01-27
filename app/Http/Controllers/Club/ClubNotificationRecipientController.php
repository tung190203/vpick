<?php

namespace App\Http\Controllers\Club;

use App\Helpers\ResponseHelper;
use App\Models\Club\Club;
use App\Models\Club\ClubNotification;
use App\Models\Club\ClubNotificationRecipient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClubNotificationRecipientController extends Controller
{
    public function index(Request $request, $clubId, $notificationId)
    {
        $notification = ClubNotification::where('club_id', $clubId)->findOrFail($notificationId);

        $validated = $request->validate([
            'is_read' => 'sometimes|boolean',
        ]);

        $query = $notification->recipients()->with('user');

        if (isset($validated['is_read'])) {
            $query->where('is_read', $validated['is_read']);
        }

        $recipients = $query->get();

        return ResponseHelper::success([
            'data' => $recipients,
            'total' => $recipients->count(),
            'read_count' => $notification->readRecipients()->count(),
            'unread_count' => $notification->unreadRecipients()->count(),
        ], 'Lấy danh sách người nhận thành công');
    }

    public function store(Request $request, $clubId, $notificationId)
    {
        $notification = ClubNotification::where('club_id', $clubId)->findOrFail($notificationId);
        $userId = auth()->id();

        $club = $notification->club;
        $member = $club->members()->where('user_id', $userId)->first();
        if (!$member || !in_array($member->role, ['admin', 'manager', 'secretary'])) {
            return ResponseHelper::error('Chỉ admin/manager/secretary mới có quyền thêm người nhận', 403);
        }

        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $added = [];
        foreach ($validated['user_ids'] as $userId) {
            if (!$notification->recipients()->where('user_id', $userId)->exists()) {
                $recipient = ClubNotificationRecipient::create([
                    'club_notification_id' => $notification->id,
                    'user_id' => $userId,
                    'is_read' => false,
                ]);
                $recipient->load('user');
                $added[] = $recipient;
            }
        }

        return ResponseHelper::success([
            'added_count' => count($added),
            'data' => $added,
        ], 'Thêm người nhận thành công');
    }

    public function getRead(Request $request, $clubId, $notificationId)
    {
        $notification = ClubNotification::where('club_id', $clubId)->findOrFail($notificationId);

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
        ]);

        $perPage = $validated['page'] ?? 15;
        $recipients = $notification->readRecipients()
            ->with('user')
            ->paginate($perPage);

        return ResponseHelper::success([
            'data' => $recipients->items(),
            'current_page' => $recipients->currentPage(),
            'per_page' => $recipients->perPage(),
            'total' => $recipients->total(),
            'last_page' => $recipients->lastPage(),
        ], 'Lấy danh sách người đã đọc thành công');
    }

    public function getUnread(Request $request, $clubId, $notificationId)
    {
        $notification = ClubNotification::where('club_id', $clubId)->findOrFail($notificationId);

        $validated = $request->validate([
            'page' => 'sometimes|integer|min:1',
        ]);

        $perPage = $validated['page'] ?? 15;
        $recipients = $notification->unreadRecipients()
            ->with('user')
            ->paginate($perPage);

        return ResponseHelper::success([
            'data' => $recipients->items(),
            'current_page' => $recipients->currentPage(),
            'per_page' => $recipients->perPage(),
            'total' => $recipients->total(),
            'last_page' => $recipients->lastPage(),
        ], 'Lấy danh sách người chưa đọc thành công');
    }
}
