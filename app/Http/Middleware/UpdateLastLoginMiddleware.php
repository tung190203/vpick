<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastLoginMiddleware
{
    /** Chỉ cập nhật last_login nếu đã quá X phút (tránh ghi DB liên tục). */
    private const THROTTLE_MINUTES = 5;

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if ($user instanceof User && $user->exists) {
            $lastLogin = $user->last_login;
            $shouldUpdate = !$lastLogin
                || $lastLogin->diffInMinutes(now()) >= self::THROTTLE_MINUTES;

            if ($shouldUpdate) {
                $user->update(['last_login' => now()]);
            }
        }

        return $next($request);
    }
}
