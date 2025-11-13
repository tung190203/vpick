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
use Google_Client;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'nullable|string',
        ]);

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $credentials = [$loginField => $request->login, 'password' => $request->password];
        $exits = User::where($loginField, $request->login)->first();
        if (!$exits) {
            return ResponseHelper::error('Người dùng không tồn tại', 404, [
                'status_code' => 'USER_NOT_FOUND'
            ]);
        }
        if(!$exits->email_verified_at) {
            $exits->notify(new VerifyEmailNotification($loginField, $request->login));
            return ResponseHelper::error('Vui lòng xác minh email trước khi đăng nhập', 403, [
                'status_code' => 'OTP_PENDING'
            ]);
        }

        if (!$exits->password) {
            $exits->notify(new VerifyEmailNotification($loginField, $request->login));
            return ResponseHelper::error('Bạn chưa hoàn tất đăng ký mật khẩu', 403, [
                'status_code' => 'PASSWORD_PENDING'
            ]);
        }

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return ResponseHelper::error('Sai thông tin đăng nhập', 401, [
                    'status_code' => 'INVALID_CREDENTIALS'
                ]);
            }
        } catch (JWTException $e) {
            return ResponseHelper::error('Không thể tạo token', 500, [
                'status_code' => 'TOKEN_ERROR'
            ]);
        }

        $user = Auth::user();

        $accessToken = JWTAuth::claims(['type' => 'access'])->fromUser($user);
        $refreshToken = JWTAuth::claims(['type' => 'refresh', 'exp' => now()->addDays(30)->timestamp])->fromUser($user);
        $user->last_login = now();
        $user->save();

        return ResponseHelper::success($this->responseWithToken($accessToken, $refreshToken, $user), 'Đăng nhập thành công');
    }

    public function register(Request $request)
    {
        $request->validate(['login' => 'required|string']);

        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $existingUser = User::where($loginField, $request->login)->first();

        if ($existingUser) {
            if (!$existingUser->email_verified_at) {
                $existingUser->notify(new VerifyEmailNotification($loginField, $request->login));
                return ResponseHelper::error('Vui lòng xác minh email trước khi đăng nhập', 403, [
                    'status_code' => 'OTP_PENDING'
                ]);
            }
            if (!$existingUser->password) {
                $existingUser->notify(new VerifyEmailNotification($loginField, $request->login));
                return ResponseHelper::error('Bạn chưa hoàn tất đăng ký mật khẩu', 403, [
                    'status_code' => 'PASSWORD_PENDING'
                ]);
            }
            return ResponseHelper::error('Email hoặc số điện thoại đã được sử dụng.', 400, ['status_code' => 'REGISTERED']);
        }

        $user = User::create([
            $loginField => $request->login,
            'avatar_url' => asset('images/default-avatar.png'),
            'full_name' => 'PickiUser' . Str::random(5),
        ]);

        $user->notify(new VerifyEmailNotification($loginField, $request->login));

        return ResponseHelper::success(['status_code' => 'OTP_PENDING'], 'Đăng ký thành công. Vui lòng xác minh tài khoản.');
    }

    public function verifyOtp(Request $request)
    {
        $request->validate(['login' => 'required|string', 'otp' => 'required|digits:6']);

        $type = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $record = DB::table('verification_codes')->where('type', $type)->where('identifier', $request->login)->first();

        if (!$record) {
            return ResponseHelper::error('Không tìm thấy mã xác minh.', 404, ['status_code' => 'OTP_NOT_FOUND']);
        }
        if ($record->otp !== $request->otp) {
            return ResponseHelper::error('Mã OTP không đúng.', 400, ['status_code' => 'OTP_INVALID']);
        }
        if (now()->greaterThan($record->expires_at)) {
            return ResponseHelper::error('Mã OTP đã hết hạn.', 400, ['status_code' => 'OTP_EXPIRED']);
        }

        $user = User::where($type, $request->login)->first();
        if (!$user) return ResponseHelper::error('Người dùng không tồn tại.', 404, ['status_code' => 'USER_NOT_FOUND']);

        $user->email_verified_at = now();
        $user->save();
        DB::table('verification_codes')->where('type', $type)->where('identifier', $request->login)->delete();

        $status = $user->password ? 'VERIFIED' : 'PASSWORD_PENDING';
        return ResponseHelper::success(['status_code' => $status], 'Xác minh thành công');
    }

    public function resendOtp(Request $request)
    {
        $request->validate(['login' => 'required|string']);
        $type = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($type, $request->login)->first();

        if (!$user) return ResponseHelper::error('Không tìm thấy người dùng.', 404, ['status_code' => 'USER_NOT_FOUND']);

        $user->notify(new VerifyEmailNotification($type, $request->login));
        return ResponseHelper::success(['status_code' => 'OTP_PENDING'], 'Mã OTP mới đã được gửi.');
    }

    public function fillPassword(Request $request)
    {
        $request->validate(['login' => 'required|string', 'password' => 'required|min:6|confirmed']);
        $type = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($type, $request->login)->first();

        if (!$user) return ResponseHelper::error('Không tìm thấy người dùng.', 404, ['status_code' => 'USER_NOT_FOUND']);
        if ($user->password) return ResponseHelper::error('Người dùng đã có mật khẩu.', 400, ['status_code' => 'PASSWORD_EXISTS']);

        $user->password = $request->password;
        $user->save();

        return ResponseHelper::success(['status_code' => 'COMPLETED'], 'Hoàn tất đăng ký');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if (!$user) return ResponseHelper::error('Email không tồn tại', 404, ['status_code' => 'USER_NOT_FOUND']);

        Mail::to($user->email)->send(new ResetPasswordMail($request->email));
        return ResponseHelper::success(['status_code' => 'OTP_PENDING'], 'Đã gửi email đặt lại mật khẩu.');
    }

    public function verifyOtpPassword(Request $request)
    {
        $request->validate(['email' => 'required|email', 'otp' => 'required|digits:6']);
        $record = DB::table('verification_codes')->where('type', 'email')->where('identifier', $request->email)->first();

        if (!$record) return ResponseHelper::error('Không tìm thấy mã xác minh.', 404, ['status_code' => 'OTP_NOT_FOUND']);
        if ($record->otp !== $request->otp) return ResponseHelper::error('Mã OTP không đúng.', 400, ['status_code' => 'OTP_INVALID']);
        if (now()->greaterThan($record->expires_at)) return ResponseHelper::error('Mã OTP đã hết hạn.', 400, ['status_code' => 'OTP_EXPIRED']);

        DB::table('verification_codes')->where('type', 'email')->where('identifier', $request->email)->delete();
        return ResponseHelper::success(['status_code' => 'VERIFIED'], 'Xác minh OTP thành công. Bạn có thể đặt lại mật khẩu.');
    }

    public function resendOtpPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);
        $user = User::where('email', $request->email)->first();
        if (!$user) return ResponseHelper::error('Không tìm thấy người dùng.', 404, ['status_code' => 'USER_NOT_FOUND']);

        Mail::to($user->email)->send(new ResetPasswordMail($request->email));
        return ResponseHelper::success(['status_code' => 'OTP_PENDING'], 'Mã OTP mới đã được gửi đến email của bạn.');
    }

    public function resetPassword(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required|min:6|confirmed']);
        $user = User::where('email', $request->email)->first();
        if (!$user) return ResponseHelper::error('Người dùng không tồn tại.', 404, ['status_code' => 'USER_NOT_FOUND']);

        $user->password = $request->password;
        $user->save();

        return ResponseHelper::success(['status_code' => 'COMPLETED'], 'Mật khẩu đã được đặt lại thành công');
    }

    public function refresh(Request $request)
    {
        $refreshToken = $request->bearerToken();
        if (!$refreshToken) return ResponseHelper::error('Token không được cung cấp', 401, ['status_code' => 'TOKEN_MISSING']);

        try {
            $payload = JWTAuth::setToken($refreshToken)->getPayload();
            if ($payload->get('type') !== 'refresh') return ResponseHelper::error('Sai loại token', 401, ['status_code' => 'INVALID_TOKEN']);

            $user = User::find($payload->get('sub'));
            if (!$user) return ResponseHelper::error('Người dùng không tồn tại', 404, ['status_code' => 'USER_NOT_FOUND']);

            $newAccessToken = JWTAuth::claims(['type' => 'access'])->fromUser($user);
            return ResponseHelper::success([
                'access_token' => $newAccessToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'status_code' => 'REFRESHED'
            ], 'Làm mới access token thành công');
        } catch (\Exception $e) {
            return ResponseHelper::error('Refresh token không hợp lệ hoặc đã hết hạn', 401, ['status_code' => 'INVALID_TOKEN']);
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
                return ResponseHelper::success(array_merge($this->responseWithToken($accessToken, $refreshToken, $user), ['status_code' => 'VERIFIED']), 'Đăng nhập bằng Google thành công');
            } else {
                return redirect(config('app.redirect_success_url') . '/login-success?' . http_build_query([
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                ]));
            }
        } catch (\Exception $e) {
            return ResponseHelper::error('Không thể đăng nhập bằng Google', 500, ['status_code' => 'OAUTH_FAILED']);
        }
    }

    public function loginWithGoogle(Request $request)
    {
        $request->validate(['id_token' => 'required|string']);
        $idToken = $request->input('id_token');
        $validClients = [
            'android' => config('services.google.android_client_id'),
            'ios' => config('services.google.ios_client_id'),
            'web' => config('services.google.client_id'),
        ];

        $client = new Google_Client();
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) return ResponseHelper::error('Token Google không hợp lệ', 401, ['status_code' => 'INVALID_TOKEN']);

        $aud = $payload['aud'] ?? null;
        $platform = collect($validClients)->search($aud);
        if ($platform === false) return ResponseHelper::error('Client ID không hợp lệ', 401, ['status_code' => 'INVALID_CLIENT']);

        $user = User::firstOrCreate(
            ['email' => $payload['email']],
            [
                'full_name' => $payload['name'] ?? null,
                'google_id' => $payload['sub'],
                'avatar_url' => $payload['picture'] ?? null,
                'password' => Hash::make(Str::random(16)),
                'email_verified_at' => now(),
            ]
        );

        $accessToken = JWTAuth::claims(['type' => 'access'])->fromUser($user);
        $refreshToken = JWTAuth::claims(['type' => 'refresh', 'exp' => now()->addDays(30)->timestamp])->fromUser($user);
        $user->last_login = now();
        $user->save();

        $response = $this->responseWithToken($accessToken, $refreshToken, $user);
        $response['platform'] = $platform;
        $response['status_code'] = 'VERIFIED';

        return ResponseHelper::success($response, 'Đăng nhập bằng Google thành công');
    }

    public function redirectToFacebook()
    {
        return Socialite::driver('facebook')->stateless()->redirect();
    }

    public function handleFacebookCallback(Request $request)
    {
        try {
            $fbUser = Socialite::driver('facebook')->stateless()->user();
            $avatarContent = file_get_contents($fbUser->getAvatar());
            $avatarName = 'avatars/' . uniqid() . '.jpg';
            Storage::disk('public')->put($avatarName, $avatarContent);

            $user = User::firstOrCreate(
                ['email' => $fbUser->getEmail()],
                [
                    'full_name' => $fbUser->getName(),
                    'facebook_id' => $fbUser->getId(),
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
                return ResponseHelper::success(array_merge($this->responseWithToken($accessToken, $refreshToken, $user), ['status_code' => 'VERIFIED']), 'Đăng nhập bằng Facebook thành công');
            } else {
                return redirect(config('app.redirect_success_url') . '/login-success?' . http_build_query([
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                ]));
            }
        } catch (\Exception $e) {
            return ResponseHelper::error('Không thể đăng nhập bằng Facebook', 500, ['status_code' => 'OAUTH_FAILED']);
        }
    }

    public function me(Request $request)
    {
        return ResponseHelper::success(new UserResource($request->user()), 'Lấy thông tin người dùng thành công', 200);
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
