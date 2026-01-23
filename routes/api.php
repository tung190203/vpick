<?php

use App\Http\Controllers\MiniTournamentStaffController;
use App\Http\Controllers\UserMatchStatsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CompetitionLocationController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MatchesController;
use App\Http\Controllers\MiniMatchController;
use App\Http\Controllers\MiniParticipantController;
use App\Http\Controllers\MiniTournamentNotificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ParticipantController;
use App\Http\Controllers\TournamentController;
use App\Http\Controllers\TournamentTypeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\ClubController;
use App\Http\Controllers\CompetitionLocationYardController;
use App\Http\Controllers\DeviceTokenController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MiniTournamentController;
use App\Http\Controllers\SendMessageController;
use App\Http\Controllers\SportController;
use App\Http\Controllers\SystemNotificationController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TournamentStaffController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

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

Route::post('/broadcasting/auth', function () {
    return Broadcast::auth(request());
})->middleware('auth:api');

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
    Route::get('/apple/redirect', [AuthController::class, 'redirectToApple']);
    Route::post('/apple/callback', [AuthController::class, 'handleAppleCallback']);

    // Mobile login with Google
    Route::post('/google', [AuthController::class, 'loginWithGoogle']);
    Route::post('/facebook', [AuthController::class, 'loginWithFacebook']);
    Route::post('/apple',[AuthController::class, 'loginWithApple']);
});

Route::get('/verify-email', [VerificationController::class, 'verify']);
Route::post('/resend-email', [VerificationController::class, 'resend']);

// Public route - không cần accessToken
Route::get('/tournament-detail/{id}/bracket', [TournamentController::class, 'getBracket']);

Route::middleware(['auth:api'])->group(function () {
    Route::post('/device-token/sync', [DeviceTokenController::class, 'sync']);
    Route::post('/notifications/setting', [DeviceTokenController::class, 'update']);
    Route::delete('/device-token/delete', [DeviceTokenController::class, 'destroy']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::prefix('user')->group(function () {
        Route::match(['get', 'post'], '/index', [UserController::class, 'index']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/update', [UserController::class, 'update']);
        Route::delete('/delete/{id}', [UserController::class, 'destroy']);
        Route::get('/matches/dataset', [UserMatchStatsController::class, 'dataset']);
        Route::get('/matches/list',[UserMatchStatsController::class, 'matchesBySportId']);
        Route::post('/change-email', [UserController::class, 'changeEmail']);
        Route::post('/verify-change-email', [UserController::class, 'verifyChangeEmail']);
        Route::post('/resend-change-email-otp', [UserController::class, 'resendChangeEmailOtp']);
    });
    Route::prefix('tournaments')->group(function () {
        Route::get('/index', [TournamentController::class, 'index']);
        Route::post('/store', [TournamentController::class, 'store']);
        Route::get('/{id}', [TournamentController::class, 'show']);
        Route::post('/update/{id}', [TournamentController::class, 'update']);
        Route::post('/delete', [TournamentController::class, 'destroy']);
        Route::get('/{id}/bracket', [TournamentController::class, 'getBracket']);
    });

    Route::prefix('tournament-staff')->group(function () {
        Route::post('/add/{tournamentId}', [TournamentStaffController::class, 'addStaff']);
    });

    Route::prefix('mini-tournament-staff')->group(function () {
        Route::post('/add/{tournamentId}', [MiniTournamentStaffController::class, 'addStaff']);
    });

    Route::prefix('tournament-types')->group(function () {
        Route::post('/store', [TournamentTypeController::class, 'store']);
        Route::match(['put', 'patch'], '/{tournamentType}', [TournamentTypeController::class, 'update']);
        Route::get('/{tournamentType}', [TournamentTypeController::class, 'show']);
        Route::delete('/{tournamentType}', [TournamentTypeController::class, 'destroy']);
        Route::get('/{tournamentType}/bracket', [TournamentTypeController::class, 'getBracket']);
        Route::get('/{tournamentId}/rank', [TournamentTypeController::class, 'getRank']);
        Route::get('/{tournamentType}/advancement-status', [TournamentTypeController::class, 'getAdvancementStatus']);
        Route::post('/{tournamentType}/regenerate-matches', [TournamentTypeController::class, 'regenerateMatches']);
        // manual
        Route::get('/{tournamentType}/groups-with-teams', [TournamentTypeController::class, 'getGroupsWithTeams']);
        Route::post('/{tournamentType}/assign-teams-and-generate', [TournamentTypeController::class, 'assignTeamsAndGenerate']);
        Route::post('/{tournamentType}/auto-generate-matches', [TournamentTypeController::class, 'autoGenerateMatches']);
    });

    Route::prefix('matches')->group(function() {
        Route::match(['get', 'post'], '/index/{tournamentTypeId}', [MatchesController::class, 'index']);
        Route::get('/detail/{matchId}', [MatchesController::class, 'detail']);
        Route::post('/update/{matchId}', [MatchesController::class, 'update']);
        Route::post('/{match}/swap', [MatchesController::class, 'swapTeams']);
        // Xác nhận kết quả (QR Code)
        Route::get('/{matchId}/generate-qr', [MatchesController::class, 'generateQr']);
        Route::post('/confirm-result/{matchId}', [MatchesController::class, 'confirmResult']);
        Route::post('/{matchId}/advance-team-manual', [MatchesController::class, 'advanceTeamManual']);
    });

    Route::prefix('participants')->group(function () {
        Route::match(['get', 'post'], '/index/{tournamentId}', [ParticipantController::class, 'index']);
        Route::post('/join/{tournamentId}', [ParticipantController::class, 'join']);
        Route::post('/confirm/{participantId}', [ParticipantController::class, 'confirm']);
        Route::post('/accept/{participantId}', [ParticipantController::class, 'acceptInvite']);
        Route::post('/decline/{participantId}', [ParticipantController::class, 'declineInvite']);
        Route::post('/invite-user/{tournamentId}', [ParticipantController::class, 'inviteUsers']);
        Route::post('/delete/{participantId}', [ParticipantController::class, 'delete']);
        Route::post('/delete-staff/{staffId}', [ParticipantController::class, 'deleteStaff']);
        Route::match(['get', 'post'], '/list-invite/{tournamentId}', [ParticipantController::class, 'listInvite']);
        Route::match(['get', 'post'], '/list-member/{tournamentId}', [ParticipantController::class, 'getParticipantsNonTeam']);
        Route::post('/candidates/{tournamentId}', [ParticipantController::class, 'getCandidates']);
    });

    Route::prefix('teams')->group(function () {
        Route::match(['get', 'post'], '/index/{tournamentId}', [TeamController::class, 'listTeams']);
        Route::post('/create/{tournamentId}', [TeamController::class, 'createTeam']);
        Route::post('/update/{teamId}', [TeamController::class, 'updateTeam']);
        Route::post('/add-member/{teamId}', [TeamController::class, 'addMember']);
        Route::post('/remove-member/{teamId}', [TeamController::class, 'removeMember']);
        Route::post('/auto-assign/{tournamentId}', [TeamController::class, 'autoAssignTeams']);
        Route::delete('/delete/{teamId}', [TeamController::class, 'deleteTeam']);
    });

    Route::prefix('club')->group(function () {
        Route::match(['get', 'post'], '/index', [ClubController::class, 'index']);
        Route::post('/store', [ClubController::class, 'store']);
        Route::get('/my-clubs', [ClubController::class, 'myClubs']);
        Route::get('/{id}', [ClubController::class, 'show']);
        Route::post('/update/{id}', [ClubController::class, 'update']);
        Route::post('/delete', [ClubController::class, 'destroy']);
        Route::post('/join/{id}', [ClubController::class, 'join']);
    });

    Route::match(['get', 'post'], '/home', [HomeController::class, 'index']);
    Route::match(['get', 'post'], '/locations', [LocationController::class, 'index']);
    // search geocoding
    Route::get('/search-location', [UserController::class, 'searchLocation']);
    Route::get('/location-detail', [UserController::class, 'detailGooglePlace']);
    // Mini Tournament Routes
    Route::prefix('mini-tournaments')->group(function (): void {
        Route::match(['get', 'post'], '/index', [MiniTournamentController::class, 'index']);
        Route::post('/store', [MiniTournamentController::class, 'store']);
        Route::get('/{id}', [MiniTournamentController::class, 'show']);
        Route::post('/update/{id}', [MiniTournamentController::class, 'update']);
        Route::post('/delete/{id}', [MiniTournamentController::class,'destroy']);
    });
    // Mini Participant Routes
    Route::prefix('mini-participants')->group(function (): void {
        Route::match(['get', 'post'], '/index/{miniTournamentId}', [MiniParticipantController::class, 'index']);
        Route::post('/join/{miniTournamentId}', [MiniParticipantController::class, 'join']);
        Route::post('/confirm/{participantId}', [MiniParticipantController::class, 'confirm']);
        Route::post('accept/{participantId}', [MiniParticipantController::class, 'acceptInvite']);
        Route::post('decline/{participantId}', [MiniParticipantController::class, 'declineInvite']);
        Route::post('/invite/{miniTournamentId}', [MiniParticipantController::class, 'invite']);
        Route::match(['get', 'post'], '/candidates/{miniTournamentId}', [MiniParticipantController::class, 'getCandidates']);
        Route::post('/delete/{participantId}', [MiniParticipantController::class, 'delete']);
        Route::post('/delete-staff/{staffId}', [MiniParticipantController::class, 'deleteStaff']);
    });
    // Mini Match Routes
    Route::prefix('mini-matches')->group(function (): void {
        Route::match(['get', 'post'], '/index/{miniTournamentId}', [MiniMatchController::class, 'index']);
        Route::get('/{matchId}', [MiniMatchController::class, 'show']);
        Route::post('/store/{miniTournamentId}', [MiniMatchController::class, 'store']);
        Route::post('/update/{matchId}', [MiniMatchController::class, 'update']);
        Route::post('/add-set/{matchId}', [MiniMatchController::class, 'addSetResult']);
        Route::delete('/delete-set/{matchId}/{setNumber}', [MiniMatchController::class, 'deleteSetResult']);
        Route::match(['delete', 'post'], '/delete', [MiniMatchController::class, 'destroy']);
        // Xác nhận kết quả (QR Code)
        Route::get('/{matchId}/generate-qr', [MiniMatchController::class, 'generateQr']);
        Route::post('/confirm-result/{matchId}', [MiniMatchController::class, 'confirmResult']);
        // Trình lọc trận đấu
        Route::match(['get', 'post'], '/list-match', [MiniMatchController::class, 'listMiniMatch']);
    });

    Route::prefix('send-message')->group(function () {
        Route::prefix('mini-tournament')->group(function () {
            Route::post('/{tournamentId}', [SendMessageController::class, 'storeMessageMiniTour']);
            Route::match(['get', 'post'], '/index/{tournamentId}', [SendMessageController::class, 'getMessagesMiniTour']);
        });
        Route::prefix('tournament')->group(function () {
            Route::post('/{tournamentId}', [SendMessageController::class, 'storeMessageTour']);
            Route::match(['get', 'post'], '/index/{tournamentId}', [SendMessageController::class, 'getMessagesTour']);
        });
    });

    Route::prefix('messages')->group(function () {
        Route::match(['get', 'post'], '/conversation/{userId}', [MessageController::class, 'getConversation']);
        Route::post('/store', [MessageController::class, 'store']);
        Route::post('/mark-as-read/{senderId}', [MessageController::class, 'markConversationAsRead']);
    });

    Route::prefix('competition-locations')->group(function () {
        Route::match(['get', 'post'], '/index', [CompetitionLocationController::class, 'index']);
    });

    Route::prefix('follows')->group(function () {
        Route::match(['get', 'post'], '/index', [FollowController::class, 'index']);
        Route::post('/store', [FollowController::class, 'store']);
        Route::delete('/delete', [FollowController::class, 'destroy']);
        Route::match(['get', 'post'], '/list-friends', [FollowController::class, 'getFriends']);
    });

    Route::prefix('sports')->group(function () {
        Route::match(['get', 'post'], '/index', [SportController::class, 'index']);
        Route::post('/update/{id}', [SportController::class, 'update']);
    });
    Route::prefix('facilities')->group(function () {
        Route::get('/index', [FacilityController::class, 'index']);
    });

    Route::prefix('competition-location-yards')->group(function () {
        Route::get('/index', [CompetitionLocationYardController::class, 'index']);
    });
    Route::prefix('notifications')->group(function () {
        Route::match(['get', 'post'], '/index', [NotificationController::class, 'index']);
        Route::post('/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/delete', [NotificationController::class, 'delete']);
    });
    Route::prefix('mini-tournament-notifications')->group(function () {
        Route::post('/subscribe/{miniTournamentId}', [MiniTournamentNotificationController::class, 'subscribe']);
        Route::post('/unsubscribe/{miniTournamentId}', [MiniTournamentNotificationController::class, 'unsubscribe']);
    });

    Route::prefix('map')->group(function () {
        Route::match(['get', 'post'], '/match', [MapController::class, 'getMatch']);
    });

    Route::prefix('banners')->group(function () {
        Route::post('/store', [BannerController::class, 'store']);
    });
});
