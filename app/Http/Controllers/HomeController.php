<?php

namespace App\Http\Controllers;

use App\Http\Resources\BannerResource;
use App\Http\Resources\ClubResource;
use App\Http\Resources\MatchesResource;
use App\Http\Resources\TournamentResource;
use App\Models\Banner;
use App\Models\Club;
use App\Models\Matches;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $userId = auth()->user()->id;
        $upcomingMatches = Matches::withFullRelations()
            ->where(function ($q) use ($userId) {
                $q->whereHas('participant1', function ($q2) use ($userId) {
                    $q2->where(function ($inner) use ($userId) {
                        $inner->where('user_id', $userId)
                            ->orWhereHas('team.members', function ($m) use ($userId) {
                                $m->where('user_id', $userId);
                            });
                    });
                })
                    ->orWhereHas('participant2', function ($q2) use ($userId) {
                        $q2->where(function ($inner) use ($userId) {
                            $inner->where('user_id', $userId)
                                ->orWhereHas('team.members', function ($m) use ($userId) {
                                    $m->where('user_id', $userId);
                                });
                        });
                    });
            })
            ->where('scheduled_at', '>', Carbon::now())
            ->orderBy('scheduled_at', 'asc')
            ->take(5)
            ->get();

        $upcomingTournaments = Tournament::withFullRelations()
            ->where('start_date', '>', Carbon::now())
            ->orderBy('start_date', 'asc')
            ->take(5)
            ->get();

        $banners = Banner::where('is_active', true)
            ->orderBy('order', 'asc')
            ->get();
        $myClub = Club::with('members')
            ->whereHas('members', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->get();
        $leaderboard = Club::with('members')
            ->withMax('members', 'vndupr_score')
            ->orderByDesc('members_max_vndupr_score')
            ->take(5)
            ->get();

        return response()->json([
            'message' => 'Welcome to the Home Page!',
            'data' => [
                'upcoming_matches' => MatchesResource::collection($upcomingMatches),
                'upcoming_tournaments' => TournamentResource::collection($upcomingTournaments),
                'banners' => BannerResource::collection($banners),
                'my_club' => ClubResource::collection($myClub),
                'leaderboard' => ClubResource::collection($leaderboard),
            ],
        ]);
    }
}
