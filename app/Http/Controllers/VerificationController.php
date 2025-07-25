<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VerificationController extends Controller
{
    public function verify(Request $request)
    {
        try {
            $payload = decrypt($request->token);

            if (now()->gt(Carbon::parse($payload['expires_at']))) {
                return response()->json(['message' => 'Token xác minh đã hết hạn.'], 400);
            }

            $user = User::where('email', $payload['email'])->first();
            $user->email_verified_at = now();
            $user->save();

            return response()->json(['message' => 'Email đã được xác minh thành công.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Token xác minh không hợp lệ hoặc đã hết hạn.'], 400);
        }
    }
    public function resend(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại.'], 404);
        }
        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email đã được xác minh trước đó.'], 400);
        }
        $user->notify(new VerifyEmailNotification());

        return response()->json(['message' => 'Email xác minh đã được gửi lại.']);
    }
}
