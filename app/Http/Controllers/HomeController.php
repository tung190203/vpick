<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\BannerResource;
use App\Http\Resources\ListClubResource;
use App\Http\Resources\ListMiniTournamentResource;
use App\Http\Resources\ListTournamentResource;
use App\Models\Banner;
use App\Models\Club;
use App\Models\MiniTournament;
use App\Models\MiniTournamentStaff;
use App\Models\Tournament;
use App\Models\TournamentStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'mini_tournament_per_page' => 'sometimes|integer|min:1|max:200',
            'tournament_per_page' => 'sometimes|integer|min:1|max:200',
            'banner_per_page' => 'sometimes|integer|min:1|max:200',
            'club_per_page' => 'sometimes|integer|min:1|max:200',
            'leaderboard_per_page' => 'sometimes|integer|min:1|max:200',
        ]);
        $userId = auth()->user()->id;
        $userInfo = [
            'vndupr_score' => auth()->user()->load('vnduprScores')->vnduprScores->max('score_value') ?? 0,
            'win_rate' => 60,
            'performance' => 80
        ];
        $upcomingMiniTournaments = MiniTournament::withFullRelations()
            ->where('starts_at', '>', Carbon::now())
            ->where(function ($query) use ($userId) {
                $query->whereHas('miniTournamentStaffs', function ($staffQuery) use ($userId) {
                    $staffQuery->where('user_id', $userId)
                        ->where('role', MiniTournamentStaff::ROLE_ORGANIZER);
                })
                    ->orWhere(function ($q) use ($userId) {
                        $q->where('status', MiniTournament::STATUS_OPEN)
                            ->where(function ($subQuery) use ($userId) {
                                $subQuery->where('is_private', false)
                                    ->orWhere(function ($privateQuery) use ($userId) {
                                        $privateQuery->where('is_private', true)
                                            ->where(function ($subSubQuery) use ($userId) {
                                                $subSubQuery->whereHas('participants', fn($p) => $p->where('user_id', $userId))
                                                    ->orWhereHas('staff', fn($s) => $s->where('user_id', $userId));
                                            });
                                    });
                            });
                    });
            })
            ->orderBy('starts_at', 'asc')
            ->take($validated['mini_tournament_per_page'] ?? MiniTournament::PER_PAGE)
            ->get();


        $upcomingTournaments = Tournament::withFullRelations()
            ->where('start_date', '>', Carbon::now())
            ->where(function ($query) use ($userId) {
                $query->whereHas('tournamentStaffs', function ($staffQuery) use ($userId) {
                    $staffQuery->where('user_id', $userId)
                        ->where('role', TournamentStaff::ROLE_ORGANIZER);
                })
                    ->orWhere(function ($q) use ($userId) {
                        $q->where('status', Tournament::OPEN)
                            ->where(function ($subQuery) use ($userId) {
                                $subQuery->where('is_private', false)
                                    ->orWhere(function ($privateQuery) use ($userId) {
                                        $privateQuery->where('is_private', true)
                                            ->where(function ($subSubQuery) use ($userId) {
                                                $subSubQuery->whereHas('participants', function ($p) use ($userId) {
                                                    $p->where('user_id', $userId);
                                                })
                                                    ->orWhereHas('tournamentStaffs', function ($s) use ($userId) {
                                                        $s->where('user_id', $userId);
                                                    });
                                            });
                                    });
                            });
                    });
            })
            ->orderBy('start_date', 'asc')
            ->take($validated['tournament_per_page'] ?? Tournament::PER_PAGE)
            ->get();

        $banners = Banner::where('is_active', true)
            ->orderBy('order', 'asc')
            ->take($validated['banner_per_page'] ?? Banner::PER_PAGE)
            ->get();
        $myClub = Club::with('members')
            ->whereHas('members', fn($q) => $q->where('user_id', $userId))
            ->take($validated['club_per_page'] ?? Club::PER_PAGE)
            ->get();

        $leaderboard = Club::with(['members.vnduprScores' => fn($q) => $q->where('score_type', 'vndupr_score')])
            ->get()
            ->map(function ($club) {
                $club->members_max_vndupr_score = $club->members
                    ->map(function ($member) {
                        return $member->vnduprScores->max('score_value') ?? 0;
                    })
                    ->max();
                return $club;
            })
            ->sortByDesc('members_max_vndupr_score')
            ->take($validated['leaderboard_per_page'] ?? Club::PER_PAGE);

        $data = [
            'user_info' => $userInfo,
            'upcoming_mini_tournament' => ListMiniTournamentResource::collection($upcomingMiniTournaments),
            'upcoming_tournaments' => ListTournamentResource::collection($upcomingTournaments),
            'banners' => BannerResource::collection($banners),
            'my_club' => ListClubResource::collection($myClub),
            'leaderboard' => ListClubResource::collection($leaderboard),
        ];

        return ResponseHelper::success($data, 'Lấy dữ liệu thành công');
    }
}
