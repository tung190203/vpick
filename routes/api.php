<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompetitionLocationController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MiniMatchController;
use App\Http\Controllers\MiniParticipantController;
use App\Http\Controllers\MiniTournamentNotificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\CompetitionLocationYardController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MiniTournamentController;
use App\Http\Controllers\SendMessageController;
use App\Http\Controllers\SportController;
use Illuminate\Support\Facades\Route;

/*
------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/refresh-token', [AuthController::class, 'refresh']);
    Route::post('/fill-password', [AuthController::class, 'fillPassword']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/verify-otp-password', [AuthController::class, 'verifyOtpPassword']);
    Route::post('/resend-otp-password', [AuthController::class, 'resendOtpPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
    Route::get('/facebook/redirect', [AuthController::class, 'redirectToFacebook']);
    Route::get('/facebook/callback', [AuthController::class, 'handleFacebookCallback']);

    // Mobile login with Google
    Route::post('/google', [AuthController::class, 'loginWithGoogle']);
});

Route::get('/verify-email', [VerificationController::class, 'verify']);
Route::post('/resend-email', [VerificationController::class, 'resend']);

Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::prefix('user')->group(function () {
        Route::match(['get', 'post'], '/index', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/update', [UserController::class, 'update']);
        Route::delete('/delete/{id}', [UserController::class, 'destroy']);
    });
    Route::prefix('tournaments')->group(function () {
        Route::get('/index', [TournamentController::class, 'index']);
        Route::post('/store', [TournamentController::class, 'store']);
        Route::get('/{id}', [TournamentController::class, 'show']);
        Route::post('/update/{id}', [TournamentController::class, 'update']);
        Route::post('/delete', [TournamentController::class, 'destroy']);
    });

    Route::prefix('club')->group(function () {
        Route::get('/index', [ClubController::class, 'index']);
        Route::post('/store', [ClubController::class, 'store']);
        Route::get('/{id}', [ClubController::class, 'show']);
        Route::post('/update/{id}', [ClubController::class, 'update']);
        Route::post('/delete', [ClubController::class, 'destroy']);
        Route::post('/join/{id}', [ClubController::class, 'join']);
    });

    Route::get('/home', [HomeController::class, 'index']);
    Route::get('/locations', [LocationController::class, 'index']);
    // search geocoding
    Route::get('/search-location', [UserController::class, 'searchLocation']);
    Route::get('/location-detail', [UserController::class, 'detailGooglePlace']);
    // Mini Tournament Routes
    Route::prefix('mini-tournaments')->group(function (): void {
        Route::get('/index', [MiniTournamentController::class, 'index']);
        Route::post('/store', [MiniTournamentController::class, 'store']);
        Route::get('/{id}', [MiniTournamentController::class, 'show']);
        Route::post('/update/{id}', [MiniTournamentController::class, 'update']);
    });
    // Mini Participant Routes
    Route::prefix('mini-participants')->group(function (): void {
        Route::get('/index/{miniTournamentId}', [MiniParticipantController::class, 'index']);
        Route::post('/join/{miniTournamentId}', [MiniParticipantController::class, 'join']);
        Route::post('/confirm/{participantId}', [MiniParticipantController::class, 'confirm']);
        Route::post('accept/{participantId}', [MiniParticipantController::class, 'acceptInvite']);
        Route::post('/invite/{miniTournamentId}', [MiniParticipantController::class, 'invite']);
        Route::get('/{id}/candidates', [MiniParticipantController::class, 'getCandidates']);
        Route::post('/delete/{participantId}', [MiniParticipantController::class, 'delete']);
    });
    // Mini Match Routes
    Route::prefix('mini-matches')->group(function (): void {
        Route::match(['get', 'post'], '/index/{miniTournamentId}', [MiniMatchController::class, 'index']);
        Route::post('/store/{miniTournamentId}', [MiniMatchController::class, 'store']);
        Route::post('/update/{matchId}', [MiniMatchController::class, 'update']);
        Route::post('/add-set/{matchId}', [MiniMatchController::class, 'addSetResult']);
        Route::delete('/delete-set/{matchId}/{setNumber}', [MiniMatchController::class, 'deleteSetResult']);
        Route::delete('/delete/{matchId}', [MiniMatchController::class, 'destroy']);
        // Xác nhận kết quả (QR Code)
        Route::get('/{matchId}/generate-qr', [MiniMatchController::class, 'generateQr']);
        Route::post('/confirm-result/{matchId}', [MiniMatchController::class, 'confirmResult']);
        // Trình lọc trận đấu
        Route::get('/index', [MiniMatchController::class, 'listMiniMatch']);
    });

    Route::prefix('send-message')->group(function () {
        Route::post('/{tournamentId}', [SendMessageController::class, 'storeMessage']);
        Route::get('/index/{tournamentId}', [SendMessageController::class, 'getMessages']);
    });

    Route::prefix('messages')->group(function () {
        Route::get('/conversation/{userId}', [MessageController::class, 'getConversation']);
        Route::post('/store', [MessageController::class, 'store']);
        Route::post('/mark-as-read/{senderId}', [MessageController::class, 'markConversationAsRead']);
    });

    Route::prefix('competition-locations')->group(function () {
        Route::match(['get', 'post'], '/index', [CompetitionLocationController::class, 'index']);
    });

    Route::prefix('follows')->group(function () {
        Route::get('/index', [FollowController::class, 'index']);
        Route::post('/store', [FollowController::class, 'store']);
        Route::delete('/delete', [FollowController::class, 'destroy']);
        Route::get('/list-friends', [FollowController::class, 'getFriends']);
    });

    Route::prefix('sports')->group(function () {
        Route::get('/index', [SportController::class, 'index']);
    });
    Route::prefix('facilities')->group(function () {
        Route::get('/index', [FacilityController::class, 'index']);
    });

    Route::prefix('competition-location-yards')->group(function () {
        Route::get('/index', [CompetitionLocationYardController::class, 'index']);
    });
    Route::prefix('notifications')->group(function () {
        Route::get('/index', [NotificationController::class, 'index']);
        Route::post('/mark-as-read', [NotificationController::class, 'markAsRead']);
    });
    Route::prefix('mini-tournament-notifications')->group(function () {
        Route::post('/subscribe/{miniTournamentId}', [MiniTournamentNotificationController::class, 'subscribe']);
        Route::post('/unsubscribe/{miniTournamentId}', [MiniTournamentNotificationController::class, 'unsubscribe']);
    });
});