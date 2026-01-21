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

        $user = auth()->user();
        $type = $validated['type'] ?? 'all';
        $query = $user->notifications()->latest();

        if ($type === 'unread') {
            $query = $user->unreadNotifications()->latest();
        } elseif ($type === 'read') {
            $query = $user->notifications()
                ->whereNotNull('read_at')
                ->latest();
        } else {
            $query = $user->notifications()->latest();
        }
    
        $notifications = $query->paginate(
            $validated['per_page'] ?? self::DEFAULT_PER_PAGE
        );
    
        // ✅ GLOBAL COUNT (KHÔNG PHỤ THUỘC FILTER)
        $totalCount = $user->notifications()->count();
        $unreadCount = $user->unreadNotifications()->count();
        $readCount = $totalCount - $unreadCount;
    
        return ResponseHelper::success([
            'notifications' => NotificationResource::collection($notifications),
        ], 'Lấy danh sách thông báo thành công', 200, [
            'current_page' => $notifications->currentPage(),
            'per_page' => $notifications->perPage(),
            'total'        => $totalCount,
            'unread_count' => $unreadCount,
            'read_count'   => $readCount,
            'last_page'    => $notifications->lastPage(),
        ]);
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
    
    public function delete(Request $request)
    {
        $validated = $request->validate([
            'notification_id' => 'nullable|exists:notifications,id',
        ]);
    
        $user = auth()->user();
        if (!empty($validated['notification_id'])) {
            $notification = $user->notifications()
                ->where('id', $validated['notification_id'])
                ->first();
    
            if ($notification) {
                $notification->delete();
            }
        } else {
            $user->notifications()->delete();
        }

        return ResponseHelper::success(null, 'Xóa thông báo thành công');
    }
}
