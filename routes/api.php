<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\VerifiedController;
use App\Http\Controllers\ClubController;
use Illuminate\Http\Request;
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
    Route::prefix('tournament')->group(function () {
        Route::get('/list', [TournamentController::class, 'list']);
        // Route::post('/create', [TournamentController::class, 'createTournament']);
        Route::get('/{id}', [TournamentController::class, 'showTournament']);
        // Route::put('/{id}/update', [TournamentController::class, 'updateTournament']);
        // Route::delete('/{id}/delete', [TournamentController::class, 'deleteTournament']);
        Route::post('/{id}/join', [TournamentController::class, 'joinTournament']);
    });

    Route::prefix('club')->group(function () {
        Route::get('/index', [ClubController::class, 'index']);
        Route::post('/store', [ClubController::class, 'store']);
        Route::post('/update/{id}', [ClubController::class, 'update']);
        Route::get('/{id}/edit', [ClubController::class, 'edit']);
        Route::post('/delete', [ClubController::class, 'destroy']);
    });
});
