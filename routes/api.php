<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MiniMatchController;
use App\Http\Controllers\MiniParticipantController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\VerifiedController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MiniTournamentController;
use App\Http\Controllers\SendMessageController;
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
    Route::post('/refresh-token', [AuthController::class, 'refresh']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

Route::get('/verify-email', [VerificationController::class, 'verify']);
Route::post('/resend-email', [VerificationController::class, 'resend']);

Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::prefix('user')->group(function () {
        Route::post('/update', [UserController::class, 'update']);
    });
    Route::prefix('verification')->group(function () {
        Route::post('/create', [VerifiedController::class, 'create']);
        Route::get('/show', [VerifiedController::class, 'show']);
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

    Route::get('/home',[HomeController::class, 'index']);
    Route::get('/locations',[LocationController::class, 'index']);
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
        Route::post('/delete/{participantId}', [MiniParticipantController::class, 'delete']);
    });
    // Mini Match Routes
    Route::prefix('mini-matches')->group(function (): void {
        Route::get('/index/{miniTournamentId}', [MiniMatchController::class, 'index']);
        Route::post('/store/{miniTournamentId}', [MiniMatchController::class, 'store']);
        Route::post('/add-set/{matchId}', [MiniMatchController::class, 'addSetResult']);
        Route::delete('/delete-set/{matchId}/{setNumber}', [MiniMatchController::class, 'deleteSetResult']);
        Route::delete('/delete/{matchId}', [MiniMatchController::class, 'destroy']);
        // Xác nhận kết quả (QR Code)
        Route::get('/{matchId}/generate-qr', [MiniMatchController::class, 'generateQr']);
        Route::post('/confirm-result/{matchId}', [MiniMatchController::class, 'confirmResult']);
    });

    Route::prefix('send-message')->group(function () {
        Route::post('/{tournamentId}', [SendMessageController::class, 'storeMessage']);
        Route::get('/index/{tournamentId}', [SendMessageController::class, 'getMessages']);
    });
});