<?php

namespace App\Http\Controllers;

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
                return response()->json(['message' => 'Sai thông tin đăng nhập'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Không thể tạo token'], 500);
        }

        $user = Auth::user();

        if (!$user->email_verified_at) {
            return response()->json(['message' => 'Vui lòng xác minh email trước khi đăng nhập'], 403);
        }

        $refreshToken = JWTAuth::claims(['type' => 'refresh'])->fromUser(auth()->user());

        return response()->json($this->responseWithToken($token, $refreshToken, $user));
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

        return response()->json([
            'message' => 'Đăng ký thành công. Vui lòng kiểm tra email để xác minh tài khoản.'
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại'], 404);
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


        return response()->json(['message' => 'Đã gửi email đặt lại mật khẩu']);
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
            return response()->json(['message' => 'Không tìm thấy yêu cầu đặt lại mật khẩu.'], 404);
        }

        if (!Hash::check($request->token, $record->token)) {
            return response()->json(['message' => 'Token không hợp lệ hoặc đã hết hạn.'], 400);
        }

        // Đặt lại mật khẩu
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Người dùng không tồn tại.'], 404);
        }

        $user->password = $request->password;
        $user->save();

        // Xoá token sau khi đặt lại xong
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Mật khẩu đã được đặt lại thành công']);
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
            
            // if (!$user->wasRecentlyCreated) {
            //     $user->update([
            //         'full_name' => $googleUser->getName(),
            //         'google_id' => $googleUser->getId(),
            //         'avatar_url' => asset('storage/' . $avatarName),
            //     ]);
            // }
            Auth::login($user);
            $token = JWTAuth::fromUser($user);

            $refreshToken = JWTAuth::claims(['type' => 'refresh'])->fromUser(auth()->user());

            return redirect(config('app.redirect_success_url') . '/login-success?' . http_build_query([
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ]));
        } catch (\Exception $e) {
            \Log::error('Google login error: ' . $e->getMessage());
            return response()->json(['error' => 'Không thể đăng nhập bằng Google'], 500);
        }
    }
    public function me(Request $request)
    {
        // return response()->json($request->user());
        return response()->json( new UserResource($request->user()));
    }

    private function responseWithToken(string $token, string $refresh_token, object $user): array
    {
        return [
            'token' => [
                'access_token' => $token,
                'refresh_token' => $refresh_token,
                'token_type' => 'Bearer',
                'expires_in' => 3600,
            ],
            'user' => $user,
        ];
    }
}
