<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\UserResource;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
                return ResponseHelper::error('Sai thông tin đăng nhập', 401);
            }
        } catch (JWTException $e) {
            return ResponseHelper::error('Không thể tạo token', 500);
        }

        $user = Auth::user();

        if (!$user->email_verified_at) {
            return ResponseHelper::error('Vui lòng xác minh email trước khi đăng nhập', 403);
        }

        $accessToken = JWTAuth::claims(['type' => 'access'])->fromUser($user);
        $refreshToken = JWTAuth::claims(['type' => 'refresh', 'exp' => now()->addDays(30)->timestamp])->fromUser($user);

        $user->last_login = now();
        $user->save();

        return ResponseHelper::success($this->responseWithToken($accessToken, $refreshToken, $user), 'Đăng nhập thành công');
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
            'password' => $request->password,
            'avatar_url' => asset('images/default-avatar.png'),
        ]);

        $user->notify(new VerifyEmailNotification());

        return ResponseHelper::success([], 'Đăng ký thành công. Vui lòng kiểm tra email để xác minh tài khoản.');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return ResponseHelper::error('Email không tồn tại', 404);
        }
        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => bcrypt($token),
                'created_at' => now()
            ]
        );

        $resetLink = url('/reset-password?token=' . $token . '&email=' . urlencode($user->email));

        // Gửi email
        Mail::to($user->email)->send(new ResetPasswordMail($resetLink));

        return ResponseHelper::success([], 'Đã gửi email đặt lại mật khẩu. Vui lòng kiểm tra hộp thư đến.');
    }
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record) {
            return ResponseHelper::error('Không tìm thấy yêu cầu đặt lại mật khẩu.', 404);
        }

        if (!Hash::check($request->token, $record->token)) {
            return ResponseHelper::error('Token không hợp lệ hoặc đã hết hạn.', 400);
        }

        // Đặt lại mật khẩu
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return ResponseHelper::error('Người dùng không tồn tại.', 404);
        }

        $user->password = $request->password;
        $user->save();

        // Xoá token sau khi đặt lại xong
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return ResponseHelper::success([], 'Mật khẩu đã được đặt lại thành công');
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->bearerToken();

        if (!$refreshToken) {
            return ResponseHelper::error('Token không được cung cấp', 401);
        }

        try {
            $payload = JWTAuth::setToken($refreshToken)->getPayload();

            if ($payload->get('type') !== 'refresh') {
                return ResponseHelper::error('Sai loại token', 401);
            }

            $user = User::find($payload->get('sub'));
            if (!$user) {
                return ResponseHelper::error('Người dùng không tồn tại', 404);
            }

            // cấp lại access token mới
            $newAccessToken = JWTAuth::claims(['type' => 'access'])->fromUser($user);
            $data = [
                'access_token' => $newAccessToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600
            ];

            return ResponseHelper::success($data, 'Làm mới access token thành công');
        } catch (\Exception $e) {
            return ResponseHelper::error('Refresh token không hợp lệ hoặc đã hết hạn', 401);
        }
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $avatarContent = file_get_contents($googleUser->getAvatar());
            $avatarName = 'avatars/' . uniqid() . '.jpg';
            Storage::disk('public')->put($avatarName, $avatarContent);

            $user = User::firstOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'full_name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'avatar_url' => asset('storage/' . $avatarName),
                    'password' => Hash::make(Str::random(16)),
                    'email_verified_at' => now(),
                ]
            );

            Auth::login($user);

            $accessToken = JWTAuth::claims(['type' => 'access'])->fromUser($user);
            $refreshToken = JWTAuth::claims(['type' => 'refresh', 'exp' => now()->addDays(30)->timestamp])->fromUser($user);

            $user->last_login = now();
            $user->save();
            if ($request->header('User-Agent') && strpos($request->header('User-Agent'), 'MobileApp') !== false) {
                return ResponseHelper::success($this->responseWithToken($accessToken, $refreshToken, $user), 'Đăng nhập bằng Google thành công');
            } else {
                return redirect(config('app.redirect_success_url') . '/login-success?' . http_build_query([
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                ]));
            }
        } catch (\Exception $e) {
            return ResponseHelper::error('Không thể đăng nhập bằng Google', 500);
        }
    }

    public function me(Request $request)
    {
        return ResponseHelper::success(new UserResource($request->user()), 'Lấy thông tin người dùng thành công');
    }

    private function responseWithToken(string $accessToken, string $refreshToken, object $user): array
    {
        return [
            'token' => [
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ],
            'user' => new UserResource($user),
        ];
    }
}
