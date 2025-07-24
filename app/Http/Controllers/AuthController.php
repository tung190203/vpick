<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['message' => 'Sai thông tin đăng nhập'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Không thể tạo token'], 500);
        }

        $user = Auth::user();

        return response()->json($this->responseWithToken($token, $user));
    }

    public function register(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'full_name' => 'required|string',
            'password_confirmation' => 'required|same:password',
        ]);

        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        return response()->json([
            'message' => 'Đăng ký thành công',
            'user' => $user
        ], 201);
    }

    public function refresh(Request $request)
    {
        try {
            $token = $request->bearerToken();

            if (!$token) {
                return response()->json(['error' => 'Token không được cung cấp'], 401);
            }

            $newToken = JWTAuth::refresh($token);

            return response()->json([
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => 3600
            ]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token không hợp lệ hoặc đã hết hạn'], 401);
        }
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            // Tìm hoặc tạo người dùng mới
            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'full_name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => $googleUser->getAvatar(),
                    'password' => bcrypt(Str::random(16)),
                ]
            );

            $token = JWTAuth::fromUser($user);

            return redirect(config('app.redirect_success_url') . '/login-success?' . http_build_query([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Không thể đăng nhập bằng Google'], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate($request->bearerToken());
            return response()->json(['message' => 'Đăng xuất thành công']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Không thể đăng xuất'], 500);
        }
    }
    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    private function responseWithToken(string $token, object $user): array
    {
        return [
            'token' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ],
            'user' => $user,
        ];
    }
}
