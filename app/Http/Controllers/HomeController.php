<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\BannerResource;
use App\Http\Resources\ListClubResource;
use App\Http\Resources\ListMiniTournamentResource;
use App\Http\Resources\ListTournamentResource;
use App\Models\Banner;
use App\Models\Club;
use App\Models\Matches;
use App\Models\MiniMatch;
use App\Models\MiniTournament;
use App\Models\Sport;
use App\Models\Tournament;
use App\Models\VnduprHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'mini_tournament_per_page' => 'sometimes|integer|min:1|max:200',
            'tournament_per_page'      => 'sometimes|integer|min:1|max:200',
            'banner_per_page'          => 'sometimes|integer|min:1|max:200',
            'club_per_page'            => 'sometimes|integer|min:1|max:200',
            'leaderboard_per_page'     => 'sometimes|integer|min:1|max:200',
        ]);
    
        $user = auth()->user();
        $userId = $user->id;
    
        $sport = Sport::where('slug', 'pickleball')->first();
        if (!$sport) {
            return ResponseHelper::error('Sport không tồn tại.', 404);
        }
        $sportId = $sport->id;
    
        // Lấy điểm vndupr_score
        $userScore = $user->vnduprScoresBySport($sportId)->max('score_value') ?? 0;
    
        // Lấy 10 trận gần nhất
        $histories = VnduprHistory::where('user_id', $userId)
            ->latest()
            ->take(10)
            ->get();
    
        $totalMatches = $histories->count();
        $wins = 0;
        $totalPoint = 0;
    
        if ($totalMatches > 0) {
            $matchIds = $histories->pluck('match_id')->filter()->unique()->values()->all();
            $miniIds  = $histories->pluck('mini_match_id')->filter()->unique()->values()->all();
    
            $matches = Matches::whereIn('id', $matchIds)->get()->keyBy('id');
            $minis   = MiniMatch::whereIn('id', $miniIds)->get()->keyBy('id');
    
            $teamIds = $matches->pluck('winner_id')->filter()->unique()->values()->all();
            $teamMembersByTeam = collect();
            if (!empty($teamIds)) {
                $members = DB::table('team_members')
                    ->whereIn('team_id', $teamIds)
                    ->get();
                $teamMembersByTeam = $members->groupBy('team_id')
                    ->map(fn($rows) => $rows->pluck('user_id')->flip());
            }
    
            foreach ($histories->values() as $index => $history) {
                $isWin = false;
    
                // match_id: winner là team
                if ($history->match_id) {
                    $match = $matches->get($history->match_id);
                    if ($match && $match->winner_id) {
                        $teamMembers = $teamMembersByTeam->get($match->winner_id);
                        $isWin = $teamMembers ? $teamMembers->has($userId) : false;
                    }
                }
                // mini_match_id: winner là user trực tiếp
                elseif ($history->mini_match_id) {
                    $mini = $minis->get($history->mini_match_id);
                    if ($mini && $mini->participant_win_id == $userId) {
                        $isWin = true;
                    }
                }
    
                if ($isWin) {
                    $wins++;
                    $coef = $index < 3 ? 1.5 : 1.0;
                    $totalPoint += 10 * $coef;
                }
            }
        }
    
        // Tính win_rate
        $winRate = $totalMatches > 0 ? ($wins / $totalMatches) * 100 : 0;
    
        // Tính performance
        $maxPoint = 0;
        for ($i = 0; $i < $totalMatches; $i++) {
            $maxPoint += $i < 3 ? 10 * 1.5 : 10;
        }
        $performance = $maxPoint > 0 ? ($totalPoint / $maxPoint) * 100 : 0;
    
        // Build sports array
        $sports = [
            [
                'sport_id'   => $sport->id,
                'sport_icon' => $sport->icon,
                'sport_name' => $sport->name,
                'scores'     => [
                    'personal_score' => number_format($user->scores->where('score_type','personal_score')->max('score_value') ?? 0, 2),
                    'dupr_score'     => number_format($user->scores->where('score_type','dupr_score')->max('score_value') ?? 0, 2),
                    'vndupr_score'   => number_format($userScore, 2),
                ],
            ]
        ];
    
        // User info
        $userInfo = [
            'win_rate'    => round($winRate, 2),
            'performance' => round($performance, 2),
            'sports'      => $sports,
        ];
    
        // Lấy upcoming mini tournaments
        $upcomingMiniTournaments = MiniTournament::withFullRelations()
            ->whereDate('starts_at', '>', now()->toDateString())
            ->where(function ($query) use ($userId) {
                $query->whereHas('participants', fn($p) => $p->where('user_id', $userId))
                      ->orWhereHas('staff', fn($s) => $s->where('user_id', $userId));
            })
            ->orderBy('starts_at', 'asc')
            ->take($validated['mini_tournament_per_page'] ?? MiniTournament::PER_PAGE)
            ->get();
    
        // Lấy upcoming tournaments
        $upcomingTournaments = Tournament::withFullRelations()
            ->whereDate('start_date', '>', now()->toDateString())
            ->where(function ($query) use ($userId) {
                $query->whereHas('participants', fn($p) => $p->where('user_id', $userId))
                      ->orWhereHas('tournamentStaffs', fn($s) => $s->where('user_id', $userId));
            })
            ->orderBy('start_date', 'asc')
            ->take($validated['tournament_per_page'] ?? Tournament::PER_PAGE)
            ->get();
    
        // Banners
        $banners = Banner::where('is_active', true)
            ->orderBy('order', 'asc')
            ->take($validated['banner_per_page'] ?? Banner::PER_PAGE)
            ->get();
    
        // My club
        $myClub = Club::with('members')
            ->whereHas('members', fn($q) => $q->where('user_id', $userId))
            ->take($validated['club_per_page'] ?? Club::PER_PAGE)
            ->get();
    
        // Leaderboard
        $leaderboard = Club::with(['members.vnduprScores' => fn($q) => $q->where('score_type', 'vndupr_score')])
            ->get()
            ->map(function ($club) {
                $club->members_max_vndupr_score = $club->members
                    ->map(fn($member) => $member->vnduprScores->max('score_value') ?? 0)
                    ->max();
                return $club;
            })
            ->sortByDesc('members_max_vndupr_score')
            ->take($validated['leaderboard_per_page'] ?? Club::PER_PAGE);
    
        // Trả về data
        $data = [
            'user_info'              => $userInfo,
            'upcoming_mini_tournament' => ListMiniTournamentResource::collection($upcomingMiniTournaments),
            'upcoming_tournaments'     => ListTournamentResource::collection($upcomingTournaments),
            'banners'                   => BannerResource::collection($banners),
            'my_club'                   => ListClubResource::collection($myClub),
            'leaderboard'               => ListClubResource::collection($leaderboard),
        ];
    
        return ResponseHelper::success($data, 'Lấy dữ liệu thành công');
    }
    
}
