<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\SystemNotification;
use Illuminate\Http\Request;

class SystemNotificationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'data' => 'sometimes|array',
            'scheduled_at' => 'required|date|after:now',
        ]);

        $notification = SystemNotification::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'data' => $validated['data'] ?? [
                'type' => 'SYSTEM_NOTIFICATION',
            ],
            'scheduled_at' => $validated['scheduled_at']
        ]);

        return ResponseHelper::success(
            $notification,
            'Tạo thông báo hệ thống thành công',
            201
        );
    }
}
