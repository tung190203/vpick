<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeamResource;
use App\Http\Resources\UserListResource;
use Illuminate\Http\Request;
use App\Models\VnduprHistory;
use App\Models\Matches;
use App\Models\MiniMatch;
use App\Models\MatchResult;
use App\Models\MiniMatchResult;
use App\Models\MiniParticipant;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserMatchStatsController extends Controller
{
    public function dataset(Request $request)
    {
        $userId = auth()->id();
        $sportId = $request->query('sport_id');

        if (!$sportId) {
            return ResponseHelper::error('Có lỗi xảy ra trong quá trình thực thi', 400);
        }

        // Lấy VnduprHistory 365 ngày
        $histories = VnduprHistory::where('user_id', $userId)
            ->where('created_at', '>=', now()->subYear())
            ->orderBy('created_at', 'asc')
            ->get();

        if ($histories->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $matchIds = $histories->pluck('match_id')->filter()->unique();
        $miniIds  = $histories->pluck('mini_match_id')->filter()->unique();

        // Matches + filter sport_id
        $matches = Matches::with('tournamentType.tournament')
            ->whereIn('id', $matchIds)
            ->get();
        $matches = $matches->filter(fn($m) => $m->tournamentType && $m->tournamentType->tournament && $m->tournamentType->tournament->sport_id == $sportId)
            ->keyBy('id');

        // MiniMatches + filter sport_id
        $minis = MiniMatch::with('miniTournament')
            ->whereIn('id', $miniIds)
            ->get()
            ->filter(fn($m) => $m->miniTournament && $m->miniTournament->sport_id == $sportId)
            ->keyBy('id');

        // Lấy kết quả
        $matchResults = MatchResult::whereIn('match_id', $matches->keys())
            ->get()
            ->groupBy('match_id');
            
        $miniResults = MiniMatchResult::whereIn('mini_match_id', $minis->keys())
            ->get()
            ->groupBy('mini_match_id');

        // Lấy mini_participants cho mini_match
        $participantIds = $minis->pluck('participant1_id')
            ->concat($minis->pluck('participant2_id'))
            ->filter()
            ->unique();
            
        $participants = collect();
        if ($participantIds->isNotEmpty()) {
            $participants = MiniParticipant::whereIn('id', $participantIds)->get()->keyBy('id');
        }

        // Team members cho matches - lấy từ home_team_id và away_team_id
        $allTeamIds = $matches->pluck('home_team_id')
            ->concat($matches->pluck('away_team_id'))
            ->filter()
            ->unique();
            
        $teamMembersByTeam = collect();
        if ($allTeamIds->isNotEmpty()) {
            $members = DB::table('team_members')->whereIn('team_id', $allTeamIds)->get();
            $teamMembersByTeam = $members->groupBy('team_id')
                ->map(fn($rows) => $rows->pluck('user_id')->all());
        }

        // Helper function để check win
        $checkWin = function($history) use ($matches, $minis, $teamMembersByTeam, $participants, $userId) {
            if ($history->match_id && $matches->has($history->match_id)) {
                $match = $matches->get($history->match_id);
                
                // Check xem user thuộc team nào
                $homeMembers = $teamMembersByTeam->get($match->home_team_id, []);
                $awayMembers = $teamMembersByTeam->get($match->away_team_id, []);
                
                $isHome = in_array($userId, $homeMembers);
                $isAway = in_array($userId, $awayMembers);

                // So sánh với winner_id - winner_id là team_id
                if ($isHome && $match->winner_id == $match->home_team_id) return true;
                if ($isAway && $match->winner_id == $match->away_team_id) return true;
                
                return false;
            } elseif ($history->mini_match_id && $minis->has($history->mini_match_id)) {
                $mini = $minis->get($history->mini_match_id);
                
                // Lấy user_id từ mini_participant
                $participant1 = $participants->get($mini->participant1_id);
                $participant2 = $participants->get($mini->participant2_id);
                
                if (!$participant1 || !$participant2) return false;
                
                $user1Id = $participant1->user_id;
                $user2Id = $participant2->user_id;
                
                // Check winner - participant_win_id là participant_id của người thắng
                if ($userId == $user1Id && $mini->participant_win_id == $mini->participant1_id) return true;
                if ($userId == $user2Id && $mini->participant_win_id == $mini->participant2_id) return true;
                
                return false;
            }
            return false;
        };

        // Helper function tính win_rate và performance
        $calculateStats = function($historiesCollection) use ($checkWin) {
            // Lấy 10 trận gần nhất
            $recentMatches = $historiesCollection->sortByDesc('created_at')->take(10)->values();
            $totalMatches = $recentMatches->count();
            
            if ($totalMatches == 0) {
                return ['win_rate' => 0, 'performance' => 0];
            }
            
            // Tính win_rate
            $winCount = $recentMatches->filter($checkWin)->count();
            $win_rate = round(($winCount / $totalMatches) * 100, 2);
            
            // Tính performance
            $points = 0;
            foreach ($recentMatches as $index => $match) {
                if ($checkWin($match)) {
                    // 3 trận mới nhất (index 0, 1, 2) có hệ số 1.5
                    $multiplier = $index < 3 ? 1.5 : 1.0;
                    $points += 10 * $multiplier;
                }
            }
            
            // Tính max points
            $recent3Max = min(3, $totalMatches) * 10 * 1.5; // 3 trận mới nhất
            $older7Max = max(0, $totalMatches - 3) * 10 * 1.0; // 7 trận còn lại
            $maxPoints = $recent3Max + $older7Max;
            
            $performance = $maxPoints > 0 ? round(($points / $maxPoints) * 100, 2) : 0;
            
            return ['win_rate' => $win_rate, 'performance' => $performance];
        };
        
        // Dataset các period
        $chart = [];

        // 1. TUẦN (7 ngày gần nhất)
        $weekHistories = $histories->filter(fn($h) => Carbon::parse($h->created_at)->gte(now()->subDays(7)));
        
        // Loại trùng lặp match_id/mini_match_id cho tuần
        $uniqueWeekMatchesForStats = collect();
        foreach ($weekHistories as $history) {
            $key = $history->match_id ? 'match_'.$history->match_id : 'mini_'.$history->mini_match_id;
            if (!$uniqueWeekMatchesForStats->has($key)) {
                $uniqueWeekMatchesForStats->put($key, $history);
            }
        }
        
        $weekStats = $calculateStats($uniqueWeekMatchesForStats);
        
        // Group và loại trùng lặp match_id/mini_match_id
        $uniqueWeekMatches = [];
        foreach ($weekHistories as $history) {
            $key = $history->match_id ? 'match_'.$history->match_id : 'mini_'.$history->mini_match_id;
            if (!isset($uniqueWeekMatches[$key])) {
                $uniqueWeekMatches[$key] = $history;
            }
        }
        
        $weekData = [];
        $weekLabels = [];
        foreach ($uniqueWeekMatches as $history) {
            
            if ($history->match_id && $matches->has($history->match_id)) {
                $match = $matches->get($history->match_id);
                
                // Xác định team của user
                $homeMembers = $teamMembersByTeam->get($match->home_team_id, []);
                $awayMembers = $teamMembersByTeam->get($match->away_team_id, []);
                
                $isHome = in_array($userId, $homeMembers);
                $myTeamId = $isHome ? $match->home_team_id : $match->away_team_id;
                $opponentTeamId = $isHome ? $match->away_team_id : $match->home_team_id;
                
                $scores = [];
                $is_win = false;
                
                if ($matchResults->has($history->match_id)) {
                    // Group results by set_number - trả ra từng set
                    $resultsBySet = $matchResults[$history->match_id]->groupBy('set_number');
                    
                    foreach ($resultsBySet as $setNumber => $setResults) {
                        $my_set_score = 0;
                        $opponent_set_score = 0;
                        
                        foreach ($setResults as $r) {
                            // Chỉ check team_id, KHÔNG check user_id
                            if ($r->team_id == $myTeamId) {
                                $my_set_score += $r->score;
                            } elseif ($r->team_id == $opponentTeamId) {
                                $opponent_set_score += $r->score;
                            }
                        }
                        
                        $scores[] = [
                            'my_score' => $my_set_score,
                            'opponent_score' => $opponent_set_score
                        ];
                    }
                    
                    // Check win
                    $is_win = $match->winner_id == $myTeamId;
                }
                
                $weekLabels[] = Carbon::parse($history->created_at)->toDateString();
                $weekData[] = [
                    'scores' => $scores,
                    'is_win' => $is_win
                ];
                
            } elseif ($history->mini_match_id && $minis->has($history->mini_match_id)) {
                $mini = $minis->get($history->mini_match_id);
                
                // Lấy user_id từ participant
                $participant1 = $participants->get($mini->participant1_id);
                $participant2 = $participants->get($mini->participant2_id);
                
                $user1Id = $participant1 ? $participant1->user_id : null;
                $user2Id = $participant2 ? $participant2->user_id : null;
                
                // Xác định user nào là mình, user nào là đối thủ
                $isParticipant1 = ($userId == $user1Id);
                
                $scores = [];
                $is_win = false;
                
                if ($miniResults->has($history->mini_match_id)) {
                    // Group results by set_number - giống như match
                    $resultsBySet = $miniResults[$history->mini_match_id]->groupBy('set_number');
                    
                    foreach ($resultsBySet as $setNumber => $setResults) {
                        $my_set_score = 0;
                        $opponent_set_score = 0;
                        
                        foreach ($setResults as $r) {
                            // r->participant_id là participant id
                            if ($r->participant_id == $mini->participant1_id && $isParticipant1) {
                                $my_set_score += $r->score;
                            } elseif ($r->participant_id == $mini->participant2_id && !$isParticipant1) {
                                $my_set_score += $r->score;
                            } elseif ($r->participant_id == $mini->participant1_id && !$isParticipant1) {
                                $opponent_set_score += $r->score;
                            } elseif ($r->participant_id == $mini->participant2_id && $isParticipant1) {
                                $opponent_set_score += $r->score;
                            }
                        }
                        
                        $scores[] = [
                            'my_score' => $my_set_score,
                            'opponent_score' => $opponent_set_score
                        ];
                    }
                }
                
                // Check win
                if ($isParticipant1) {
                    $is_win = $mini->participant_win_id == $mini->participant1_id;
                } else {
                    $is_win = $mini->participant_win_id == $mini->participant2_id;
                }
                
                $weekLabels[] = Carbon::parse($history->created_at)->toDateString();
                $weekData[] = [
                    'scores' => $scores,
                    'is_win' => $is_win
                ];
            }
        }

        $chart['week'] = [
            'labels' => $weekLabels,
            'datasets' => $weekData,
            'win_rate' => $weekStats['win_rate'],
            'performance' => $weekStats['performance']
        ];

        // 2. THÁNG (30 ngày) - Tỉ lệ thắng theo ngày
        $monthHistories = $histories->filter(fn($h) => Carbon::parse($h->created_at)->gte(now()->subDays(30)));
        
        // Loại trùng lặp cho tháng
        $uniqueMonthMatchesForStats = collect();
        foreach ($monthHistories as $history) {
            $key = $history->match_id ? 'match_'.$history->match_id : 'mini_'.$history->mini_match_id;
            if (!$uniqueMonthMatchesForStats->has($key)) {
                $uniqueMonthMatchesForStats->put($key, $history);
            }
        }
        
        $monthStats = $calculateStats($uniqueMonthMatchesForStats);
        
        // Group theo ngày, loại trùng lặp match
        $dayGroups = [];
        foreach ($monthHistories as $h) {
            $date = Carbon::parse($h->created_at)->toDateString();
            $key = $h->match_id ? 'match_'.$h->match_id : 'mini_'.$h->mini_match_id;
            
            if (!isset($dayGroups[$date])) {
                $dayGroups[$date] = [];
            }
            if (!isset($dayGroups[$date][$key])) {
                $dayGroups[$date][$key] = $h;
            }
        }
        
        $monthData = [];
        foreach ($dayGroups as $date => $items) {
            $winCount = 0;
            $totalCount = count($items);
            
            foreach ($items as $h) {
                if ($checkWin($h)) $winCount++;
            }
            
            $monthData[$date] = $totalCount > 0 ? round(($winCount / $totalCount) * 100, 2) : 0;
        }
    
        // Format labels theo dd/MM
        $formattedMonthLabels = array_map(function($date) {
            return Carbon::parse($date)->format('d/m');
        }, array_keys($monthData));
        
        $chart['30days'] = [
            'labels' => $formattedMonthLabels,
            'datasets' => array_values($monthData),
            'win_rate' => $monthStats['win_rate'],
            'performance' => $monthStats['performance']
        ];

        // 3. QUÝ (90 ngày) - Tỉ lệ thắng theo tuần
        $quarterHistories = $histories->filter(fn($h) => Carbon::parse($h->created_at)->gte(now()->subDays(90)));
        
        // Loại trùng lặp cho quý
        $uniqueQuarterMatchesForStats = collect();
        foreach ($quarterHistories as $history) {
            $key = $history->match_id ? 'match_'.$history->match_id : 'mini_'.$history->mini_match_id;
            if (!$uniqueQuarterMatchesForStats->has($key)) {
                $uniqueQuarterMatchesForStats->put($key, $history);
            }
        }
        
        $quarterStats = $calculateStats($uniqueQuarterMatchesForStats);
        
        $weekGroups = [];
        foreach ($quarterHistories as $h) {
            $week = Carbon::parse($h->created_at)->format('Y-W');
            $key = $h->match_id ? 'match_'.$h->match_id : 'mini_'.$h->mini_match_id;
            
            if (!isset($weekGroups[$week])) {
                $weekGroups[$week] = [];
            }
            if (!isset($weekGroups[$week][$key])) {
                $weekGroups[$week][$key] = $h;
            }
        }
        
        $quarterData = [];
        foreach ($weekGroups as $week => $items) {
            $winCount = 0;
            $totalCount = count($items);
            
            foreach ($items as $h) {
                if ($checkWin($h)) $winCount++;
            }
            
            $quarterData[$week] = $totalCount > 0 ? round(($winCount / $totalCount) * 100, 2) : 0;
        }
    
        // Format labels theo Tuần/2025
        $formattedQuarterLabels = array_map(function($week) {
            // $week format: Y-W (VD: 2025-48)
            $parts = explode('-', $week);
            return ltrim($parts[1], '0') . '/' . $parts[0];
        }, array_keys($quarterData));
        
        $chart['90days'] = [
            'labels' => $formattedQuarterLabels,
            'datasets' => array_values($quarterData),
            'win_rate' => $quarterStats['win_rate'],
            'performance' => $quarterStats['performance']
        ];

        // 4. NĂM (365 ngày) - Tỉ lệ thắng theo tháng
        
        // Loại trùng lặp cho năm
        $uniqueYearMatchesForStats = collect();
        foreach ($histories as $history) {
            $key = $history->match_id ? 'match_'.$history->match_id : 'mini_'.$history->mini_match_id;
            if (!$uniqueYearMatchesForStats->has($key)) {
                $uniqueYearMatchesForStats->put($key, $history);
            }
        }
        
        $yearStats = $calculateStats($uniqueYearMatchesForStats);
        $monthGroups = [];
        foreach ($histories as $h) {
            $month = Carbon::parse($h->created_at)->format('Y-m');
            $key = $h->match_id ? 'match_'.$h->match_id : 'mini_'.$h->mini_match_id;
            
            if (!isset($monthGroups[$month])) {
                $monthGroups[$month] = [];
            }
            if (!isset($monthGroups[$month][$key])) {
                $monthGroups[$month][$key] = $h;
            }
        }
        
        $yearData = [];
        foreach ($monthGroups as $month => $items) {
            $winCount = 0;
            $totalCount = count($items);
            
            foreach ($items as $h) {
                if ($checkWin($h)) $winCount++;
            }
            
            $yearData[$month] = $totalCount > 0 ? round(($winCount / $totalCount) * 100, 2) : 0;
        }
    
        // Format labels theo MM/2025
        $formattedYearLabels = array_map(function($month) {
            // $month format: Y-m (VD: 2025-01)
            $parts = explode('-', $month);
            return $parts[1] . '/' . $parts[0];
        }, array_keys($yearData));
        
        $chart['365days'] = [
            'labels' => $formattedYearLabels,
            'datasets' => array_values($yearData),
            'win_rate' => $yearStats['win_rate'],
            'performance' => $yearStats['performance']
        ];

        return ResponseHelper::success($chart, 'Lấy dữ liệu thành công');
    }

    public function matchesBySportId(Request $request) {
        $userId = auth()->id();
        $sportId = $request->query('sport_id');
        $perPage = $request->query('per_page', 15); // Mặc định 15 items/page
        
        if (!$sportId) {
            return ResponseHelper::error('Có lỗi xảy ra trong quá trình thực thi', 400);
        }
    
        // Lấy tất cả VnduprHistory của user
        $histories = VnduprHistory::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    
        if ($histories->isEmpty()) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
                'last_page' => 1
            ]);
        }
    
        $matchIds = $histories->pluck('match_id')->filter()->unique();
        $miniIds = $histories->pluck('mini_match_id')->filter()->unique();
    
        // Lấy Matches với filter sport_id
        $matches = Matches::with(['tournamentType.tournament', 'homeTeam', 'awayTeam'])
            ->whereIn('id', $matchIds)
            ->get()
            ->filter(fn($m) => $m->tournamentType && 
                              $m->tournamentType->tournament && 
                              $m->tournamentType->tournament->sport_id == $sportId);
    
        // Lấy MiniMatches với filter sport_id
        $minis = MiniMatch::with('miniTournament')
            ->whereIn('id', $miniIds)
            ->get()
            ->filter(fn($m) => $m->miniTournament && 
                              $m->miniTournament->sport_id == $sportId);
    
        // Lấy kết quả
        $matchResults = MatchResult::whereIn('match_id', $matches->pluck('id'))
            ->get()
            ->groupBy('match_id');
            
        $miniResults = MiniMatchResult::whereIn('mini_match_id', $minis->pluck('id'))
            ->get()
            ->groupBy('mini_match_id');
    
        // Lấy participants cho mini_match
        $participantIds = $minis->pluck('participant1_id')
            ->concat($minis->pluck('participant2_id'))
            ->filter()
            ->unique();
            
        $participants = collect();
        if ($participantIds->isNotEmpty()) {
            $participants = MiniParticipant::with('user')->whereIn('id', $participantIds)->get()->keyBy('id');
        }
    
        // Lấy team members
        $allTeamIds = $matches->pluck('home_team_id')
            ->concat($matches->pluck('away_team_id'))
            ->filter()
            ->unique();
            
        $teamMembersByTeam = collect();
        if ($allTeamIds->isNotEmpty()) {
            $members = DB::table('team_members')->whereIn('team_id', $allTeamIds)->get();
            $teamMembersByTeam = $members->groupBy('team_id')
                ->map(fn($rows) => $rows->pluck('user_id')->all());
        }
    
        // Merge matches và mini matches với thông tin đầy đủ
        $allMatches = collect();
    
        // Xử lý Matches
        foreach ($matches as $match) {
            $history = $histories->where('match_id', $match->id)->first();
            if (!$history) continue;
    
            // Xác định team của user
            $homeMembers = $teamMembersByTeam->get($match->home_team_id, []);
            $awayMembers = $teamMembersByTeam->get($match->away_team_id, []);
            
            $isHome = in_array($userId, $homeMembers);
            
            // Swap để user luôn ở vị trí "home" (participant 1)
            $myTeam = $isHome ? $match->homeTeam : $match->awayTeam;
            $opponentTeam = $isHome ? $match->awayTeam : $match->homeTeam;
            $myTeamId = $isHome ? $match->home_team_id : $match->away_team_id;
            $opponentTeamId = $isHome ? $match->away_team_id : $match->home_team_id;
            
            // Tính điểm số
            $scores = [];
            $is_win = false;
            
            if ($matchResults->has($match->id)) {
                $resultsBySet = $matchResults[$match->id]->groupBy('set_number');
                
                foreach ($resultsBySet as $setNumber => $setResults) {
                    $myScore = 0;
                    $opponentScore = 0;
                    
                    foreach ($setResults as $r) {
                        if ($r->team_id == $myTeamId) {
                            $myScore += $r->score;
                        } else {
                            $opponentScore += $r->score;
                        }
                    }
                    
                    $scores[] = [
                        'my_score' => $myScore,
                        'opponent_score' => $opponentScore,
                        'set_number' => $setNumber
                    ];
                }
                
                $is_win = $match->winner_id == $myTeamId;
            }
    
            $allMatches->push([
                'type' => 'match',
                'format' => 'team',
                'id' => $match->id,
                'tournament_name' => $match->tournamentType->tournament->name ?? null,
                'my_team' => new TeamResource($myTeam),           // User team (luôn ở vị trí 1)
                'opponent_team' => new TeamResource($opponentTeam), // Đối thủ (luôn ở vị trí 2)
                'my_team_id' => $myTeamId,
                'opponent_team_id' => $opponentTeamId,
                'scores' => $scores,
                'is_win' => $is_win,
                'status' => $match->status,
                'match_date' => $match->match_date,
                'created_at' => $history->created_at
            ]);
        }
    
        // ========== XỬ LÝ MINI MATCHES - SWAP ĐỂ USER LUÔN Ở PARTICIPANT1, ĐỐI THỦ Ở PARTICIPANT2 ==========
        foreach ($minis as $mini) {
            $history = $histories->where('mini_match_id', $mini->id)->first();
            if (!$history) continue;
    
            $participant1 = $participants->get($mini->participant1_id);
            $participant2 = $participants->get($mini->participant2_id);
            
            if (!$participant1 || !$participant2) continue;
            
            $isParticipant1 = ($userId == $participant1->user_id);
            
            // Swap để user luôn ở vị trí "participant1"
            $myParticipant = $isParticipant1 ? $participant1 : $participant2;
            $opponentParticipant = $isParticipant1 ? $participant2 : $participant1;
            $myParticipantId = $isParticipant1 ? $mini->participant1_id : $mini->participant2_id;
            $opponentParticipantId = $isParticipant1 ? $mini->participant2_id : $mini->participant1_id;
            
            // Tính điểm số
            $scores = [];
            $is_win = false;
            
            if ($miniResults->has($mini->id)) {
                $resultsBySet = $miniResults[$mini->id]->groupBy('set_number');
                
                foreach ($resultsBySet as $setNumber => $setResults) {
                    $myScore = 0;
                    $opponentScore = 0;
                    
                    foreach ($setResults as $r) {
                        if (($r->participant_id == $mini->participant1_id && $isParticipant1) ||
                            ($r->participant_id == $mini->participant2_id && !$isParticipant1)) {
                            $myScore += $r->score;
                        } else {
                            $opponentScore += $r->score;
                        }
                    }
                    
                    $scores[] = [
                        'my_score' => $myScore,
                        'opponent_score' => $opponentScore,
                        'set_number' => $setNumber
                    ];
                }
            }
            
            $is_win = $isParticipant1 
                ? $mini->participant_win_id == $mini->participant1_id
                : $mini->participant_win_id == $mini->participant2_id;
    
            $allMatches->push([
                'type' => 'mini_match',
                'format' => $myParticipant->user_id == null ? 'team' : 'user',
                'id' => $mini->id,
                'tournament_name' => $mini->miniTournament->name ?? null,
                'my_participant' => new UserListResource($myParticipant->user),        // User (luôn ở vị trí 1)
                'opponent_participant' => new UserListResource($opponentParticipant->user), // Đối thủ (luôn ở vị trí 2)
                'my_participant_id' => $myParticipantId,
                'opponent_participant_id' => $opponentParticipantId,
                'scores' => $scores,
                'is_win' => $is_win,
                'status' => $mini->status,
                'match_date' => $mini->match_date,
                'created_at' => $history->created_at
            ]);
        }
    
        // Sort theo created_at giảm dần
        $allMatches = $allMatches->sortByDesc('created_at')->values();
    
        // Phân trang thủ công
        $total = $allMatches->count();
        $lastPage = ceil($total / $perPage);
        $currentPage = max(1, min($request->query('page', 1), $lastPage));
        
        $offset = ($currentPage - 1) * $perPage;
        $paginatedData = $allMatches->slice($offset, $perPage)->values();
    
        return ResponseHelper::success([
            'data' => $paginatedData,
            'pagination' => [
                'current_page' => $currentPage,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total)
            ]
        ], 'Lấy danh sách trận đấu thành công');
    }
}