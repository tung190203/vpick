<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function sync(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'platform' => 'required|in:ios,android',
        ]);

        $device = DeviceToken::updateOrCreate(
            ['token' => $request->token],
            [
                'user_id' => auth()->id(),
                'platform' => $request->platform,
                'last_seen_at' => now(),
                'is_enabled' => true,
            ]
        );

        return ResponseHelper::success($device,'Thao tác thành công', 200);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
            'enabled' => 'required|boolean',
        ]);

        $device = DeviceToken::where('token', $data['token'])
            ->where('user_id', auth()->id())
            ->update(['is_enabled' => $data['enabled']]);

        return ResponseHelper::success($device,'Thao tác thành công', 200);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string',
        ]);

        $device = DeviceToken::where('token', $data['token'])
            ->where('user_id', auth()->id())->delete();

        return ResponseHelper::success($device,'Xoá token thành công', 200);
    }
}
