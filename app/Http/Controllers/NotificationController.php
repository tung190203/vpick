<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    const DEFAULT_PER_PAGE = 15;
    public function index(Request $request)
    {
        $validated = $request->validate([
            'type' => 'nullable|in:all,unread,read',
            'per_page' => 'integer|min:1|max:200',
        ]);

        $type = $validated['type'] ?? 'all';
        $query = auth()->user()->notifications()->latest();

        if ($type === 'unread') {
            $query = auth()->user()->unreadNotifications()->latest();
        } elseif ($type === 'read') {
            $query = auth()->user()->notifications()
                ->whereNotNull('read_at')
                ->latest();
        }

        $notifications = $query->paginate($validated['per_page'] ?? self::DEFAULT_PER_PAGE);

        $data = [
            'notifications' => NotificationResource::collection($notifications),
        ];

        $meta = [
            'current_page' => $notifications->currentPage(),
            'per_page' => $notifications->perPage(),
            'total' => $notifications->total(),
            'last_page' => $notifications->lastPage(),
        ];
    
        return ResponseHelper::success($data, 'Lấy danh sách thông báo thành công', 200, $meta);
    }    

    public function markAsRead(Request $request)
    {
        $validated = $request->validate([
            'notification_id' => 'nullable|exists:notifications,id',
        ]);
    
        $user = auth()->user();
        if (!empty($validated['notification_id'])) {
            $notification = $user->notifications()
                ->where('id', $validated['notification_id'])
                ->first();
    
            if ($notification && $notification->read_at === null) {
                $notification->markAsRead();
            }
        } else {
            $user->unreadNotifications->markAsRead();
        }

        return ResponseHelper::success(null, 'Đánh dấu thông báo đã đọc thành công');
    }    
}
