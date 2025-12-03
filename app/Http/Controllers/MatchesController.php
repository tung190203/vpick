<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\MatchDetailResource;
use App\Http\Resources\MatchesResource;
use App\Models\Matches;
use App\Models\MatchResult;
use App\Models\Team;
use App\Models\TeamRanking;
use App\Models\TournamentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PoolAdvancementRule;
use App\Models\VnduprHistory;
use App\Services\TournamentService;
use Illuminate\Support\Facades\Auth;

class MatchesController extends Controller
{
    public function index(Request $request, $tournamenttypeId)
    {
        $validated = $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:200',
        ]);
        $matches = Matches::withFullRelations()
        ->where('tournament_type_id', $tournamenttypeId)
        ->paginate($validated['per_page'] ?? Matches::PER_PAGE);

    $data = [
        'matches' => MatchesResource::collection($matches),
    ];

    $meta = [
        'current_page' => $matches->currentPage(),
        'last_page'    => $matches->lastPage(),
        'per_page'     => $matches->perPage(),
        'total'        => $matches->total(),
    ];

    return ResponseHelper::success($data, 'Lấy danh sách trận đấu thành công', 200, $meta);
    }

    public function detail(Request $request, $matchId)
    {
        $match = Matches::withFullRelations()->find($matchId);
        if (!$match) {
            return ResponseHelper::error('Match not found', 404);
        }
        return ResponseHelper::success(new MatchDetailResource($match));
    }
    public function update(Request $request, $matchId)
    {
        $validated = $request->validate([
            'court' => 'nullable|integer',
            'results' => 'nullable|array',
            'results.*.id' => 'sometimes|exists:match_results,id',
            'results.*.set_number' => 'required|integer|min:1',
            'results.*.team_id' => 'required|integer|exists:teams,id',
            'results.*.score' => 'required|integer|min:0',
        ]);

        // 🔍 Lấy match + luật thi đấu
        $match = Matches::with('results', 'tournamentType')->find($matchId);
        if (!$match) {
            return ResponseHelper::error('Không tìm thấy trận đấu.', 404);
        }

        if ($match->status === Matches::STATUS_COMPLETED || $match->home_team_confirm == 1 || $match->away_team_confirm == 1) {
            return ResponseHelper::error('Kết quả trận đấu đã được xác nhận không thể thay đổi điểm số', 400);
        }

        $match->update(['court' => $validated['court'] ?? $match->court]);

        $rules = $match->tournamentType->match_rules ?? null;
        if (!$rules) {
            return ResponseHelper::error('Thể thức này chưa có luật thi đấu (match_rules).', 400);
        }

        $setsPerMatch = $rules['sets_per_match'] ?? 3;
        $pointsToWinSet = $rules['points_to_win_set'] ?? 11;
        $winningRule = $rules['winning_rule'] ?? 2; // cách biệt tối thiểu để win
        $maxPoints = $rules['max_points'] ?? $pointsToWinSet;

        if (count($validated['results'] ?? []) > $setsPerMatch * 2) {
            return ResponseHelper::error("Số sets vượt quá giới hạn ({$setsPerMatch}).", 400);
        }

        // 🔄 Gom dữ liệu theo từng set_number
        $sets = collect($validated['results'] ?? [])->groupBy('set_number');
        $keepIds = [];

        foreach ($sets as $setNumber => $setResults) {
            // chỉ xử lý khi có đủ 2 đội trong set
            if ($setResults->count() !== 2) {
                foreach ($setResults as $r) {
                    $result = $match->results()->updateOrCreate(
                        ['id' => $r['id'] ?? null],
                        [
                            'match_id' => $match->id,
                            'team_id' => $r['team_id'],
                            'score' => $r['score'],
                            'set_number' => $r['set_number'],
                            'won_match' => false,
                        ]
                    );
                    $keepIds[] = $result->id;
                }
                continue;
            }

            $teamA = $setResults[0];
            $teamB = $setResults[1];
            $A = $teamA['score'];
            $B = $teamB['score'];

            $winnerTeamId = null;

            // 🧮 Xác định đội thắng set
            if (
                ($A >= $pointsToWinSet || $B >= $pointsToWinSet) &&
                abs($A - $B) >= $winningRule
            ) {
                $winnerTeamId = $A > $B ? $teamA['team_id'] : $teamB['team_id'];
            } elseif ($A == $maxPoints || $B == $maxPoints) {
                // nếu chạm max point thì thắng luôn
                $winnerTeamId = $A > $B ? $teamA['team_id'] : $teamB['team_id'];
            }

            foreach ($setResults as $r) {
                $result = $match->results()->updateOrCreate(
                    [
                        'match_id' => $match->id,
                        'team_id' => $r['team_id'],
                        'set_number' => $r['set_number'],
                    ],
                    [
                        'score' => $r['score'],
                        'won_match' => $winnerTeamId === $r['team_id'],
                    ]
                );
                $keepIds[] = $result->id;
            }
        }

        // 🧹 Xoá kết quả thừa
        $match->results()->whereNotIn('id', $keepIds)->delete();

        $match->load('results');
        $this->calculateMatchWinner($match, $setsPerMatch);

        return ResponseHelper::success(new MatchDetailResource($match));
    }

    private function calculateMatchWinner($match, $setsPerMatch)
    {
        // Tính số set cần thắng (best-of logic). Ví dụ setsPerMatch = 3 -> need 2
        $neededToWin = intdiv($setsPerMatch, 2) + 1;

        // Thu danh sách team xuất hiện trong match results (unique)
        $teamIds = $match->results->pluck('team_id')->unique()->values()->all();

        // Nếu không đủ 2 đội (dữ liệu bất thường) thì không quyết định
        if (count($teamIds) < 2) {
            return;
        }

        // Khởi tạo wins = 0 cho mỗi team
        $setWins = array_fill_keys($teamIds, 0);

        // Đếm số set thắng (won_match = true)
        foreach ($match->results as $r) {
            if ($r->won_match) {
                if (!isset($setWins[$r->team_id]))
                    $setWins[$r->team_id] = 0;
                $setWins[$r->team_id]++;
            }
        }

        // Nếu không có set nào được đánh dấu là won_match thì không quyết (dữ liệu chưa đủ)
        if (array_sum($setWins) === 0) {
            return;
        }

        // Kiểm tra xem đã có team đạt ngưỡng thắng chưa
        $winnerTeamId = null;
        foreach ($setWins as $teamId => $wins) {
            if ($wins >= $neededToWin) {
                $winnerTeamId = $teamId;
                break;
            }
        }

        // Cập nhật match
        $match->update([
            'winner_id' => $winnerTeamId
        ]);
        if (
            $winnerTeamId &&
            in_array($match->tournamentType->format, [
                TournamentType::FORMAT_MIXED,
                TournamentType::FORMAT_ELIMINATION,
            ])
        ) {
            $this->advanceWinnerToNextRound($match, $winnerTeamId);
        }
        // Cập nhật lại bảng xếp hạng
        $this->recalculateRankings($match->tournament_type_id);
    }

    private function advanceWinnerToNextRound($match, $winnerTeamId)
    {
        $tournamentType = $match->tournamentType;
        if ((int) $match->round === 1 && $tournamentType->format === TournamentType::FORMAT_MIXED) {
            $this->checkAndAdvanceFromPool($match);
            return;
        }

        if (!$match->next_match_id) {
            return;
        }
    
        $nextMatch = Matches::find($match->next_match_id);
        if (!$nextMatch) {
            return;
        }
    
        if ($match->next_position === 'home') {
            $nextMatch->update([
                'home_team_id' => $winnerTeamId,
                'status' => Matches::STATUS_PENDING,
            ]);
        } elseif ($match->next_position === 'away') {
            $nextMatch->update([
                'away_team_id' => $winnerTeamId,
                'status' => Matches::STATUS_PENDING,
            ]);
        }
    }
    
    private function checkAndAdvanceFromPool($completedMatch)
    {
        $groupId = $completedMatch->group_id;
        if (!$groupId) {
            return;
        }
        
        $tournamentTypeId = $completedMatch->tournament_type_id;
        $allGroupMatches = Matches::where('group_id', $groupId)
            ->where('round', 1)
            ->with(['homeTeam.members', 'awayTeam.members'])
            ->get();
        
        $totalMatches = $allGroupMatches->count();
        $completedMatches = $allGroupMatches->where('status', 'completed')->count();
        $allCompleted = $allGroupMatches->every(fn($m) => $m->status === 'completed');
        
        if (!$allCompleted) {
            return;
        }
        $standings = TournamentService::calculateGroupStandings($allGroupMatches);
        $rules = PoolAdvancementRule::where('group_id', $groupId)
            ->orderBy('rank')
            ->get();
        
        if ($rules->isEmpty()) {
            return;
        }
        foreach ($rules as $rule) {
            $teamAtRank = $standings->get($rule->rank - 1);
            
            if (!$teamAtRank) {
                continue;
            }
            
            $teamId = $teamAtRank['team']['id'];
            $teamName = $teamAtRank['team']['name'];
            
            // Lấy trận knockout tương ứng
            $nextMatch = Matches::find($rule->next_match_id);
            
            if (!$nextMatch) {
                continue;
            }
            $updateData = ['status' => Matches::STATUS_PENDING];
            
            if ($rule->next_position === 'home') {
                $updateData['home_team_id'] = $teamId;
                $positionText = 'home';
            } else {
                $updateData['away_team_id'] = $teamId;
                $positionText = 'away';
            }
            
            $nextMatch->update($updateData);
        }
        $this->checkAllPoolsCompleted($tournamentTypeId);
    }  
    
    private function checkAllPoolsCompleted($tournamentTypeId)
    {
        $allPoolMatches = Matches::where('tournament_type_id', $tournamentTypeId)
            ->where('round', 1)
            ->get();
        
        if ($allPoolMatches->isEmpty()) {
            return;
        }
        
        $totalMatches = $allPoolMatches->count();
        $completedMatches = $allPoolMatches->where('status', 'completed')->count();
        $allCompleted = $allPoolMatches->every(fn($m) => $m->status === 'completed');
        
        if ($allCompleted) {
            $knockoutMatches = Matches::where('tournament_type_id', $tournamentTypeId)
                ->where('round', '>', 1)
                ->where('status', Matches::STATUS_PENDING)
                ->get();
        } else {
            $remainingGroups = $allPoolMatches->groupBy('group_id')
                ->filter(fn($matches) => $matches->some(fn($m) => $m->status !== 'completed'))
                ->count();
        }
    }

    private function recalculateRankings($tournamentTypeId)
    {
        $tournamentType = TournamentType::find($tournamentTypeId);
        if (!$tournamentType)
            return;

        $config = $tournamentType->format_specific_config ?? [];
        $rankingRules = $config['ranking'] ?? [1, 2];

        $tournament_id = $tournamentType->tournament_id;

        // 1️⃣ Lấy toàn bộ teams
        $teams = Team::where('tournament_id', $tournament_id)
            ->select('id')
            ->distinct()
            ->get();

        if ($teams->isEmpty())
            return;

        // 2️⃣ Khởi tạo thống kê
        $stats = [];
        foreach ($teams as $team) {
            $stats[$team->id] = [
                'team_id' => $team->id,
                'played' => 0,
                'wins' => 0,
                'losses' => 0,
                'points' => 0,
                'sets_won' => 0,
                'sets_lost' => 0,
                'points_won' => 0,
                'points_lost' => 0,
                'set_diff' => 0,
                'win_rate' => 0,
            ];
        }

        // 3️⃣ Lấy trận hoàn thành
        $matches = Matches::where('tournament_type_id', $tournamentTypeId)
            ->where('status', 'completed')
            ->with('results')
            ->get();

        foreach ($matches as $match) {
            $home = $match->home_team_id;
            $away = $match->away_team_id;

            $winner = $match->winner_id;
            $loser = null;
            if ($winner == $home)
                $loser = $away;
            elseif ($winner == $away)
                $loser = $home;

            foreach ([$home, $away] as $tid) {
                if (!$tid || !isset($stats[$tid]))
                    continue;
                $stats[$tid]['played']++;
            }

            if ($winner && $loser && isset($stats[$winner]) && isset($stats[$loser])) {
                $stats[$winner]['wins']++;
                $stats[$winner]['points'] += 3;
                $stats[$loser]['losses']++;
            }

            // Cộng điểm set và điểm số
            foreach ($match->results as $r) {
                if (!isset($stats[$r->team_id]))
                    continue;
                $stats[$r->team_id]['points_won'] += $r->score;
                if ($r->won_match)
                    $stats[$r->team_id]['sets_won']++;
                else
                    $stats[$r->team_id]['sets_lost']++;
            }
        }

        // 4️⃣ Tính phụ
        foreach ($stats as &$s) {
            $s['set_diff'] = $s['sets_won'] - $s['sets_lost'];
            $s['win_rate'] = $s['played'] > 0 ? round($s['wins'] / $s['played'] * 100, 2) : 0;
        }
        unset($s);

        // 5️⃣ Sắp xếp theo rule
        $sorted = collect($stats)->sort(function ($a, $b) use ($rankingRules, $matches) {
            if ($a['played'] == 0 && $b['played'] > 0)
                return 1;
            if ($b['played'] == 0 && $a['played'] > 0)
                return -1;

            foreach ($rankingRules as $ruleId) {
                switch ($ruleId) {
                    case TournamentType::RANKING_WIN_DRAW_LOSE_POINTS:
                        if ($a['points'] !== $b['points'])
                            return $b['points'] <=> $a['points'];
                        break;

                    case TournamentType::RANKING_WIN_RATE:
                        if ($a['win_rate'] !== $b['win_rate'])
                            return $b['win_rate'] <=> $a['win_rate'];
                        break;
                }
            }
            return 0;
        })->values();

        // 6️⃣ Clear cũ & cập nhật mới
        TeamRanking::where('tournament_type_id', $tournamentTypeId)->delete();

        $rank = 1;
        foreach ($sorted as $s) {
            TeamRanking::create([
                'tournament_type_id' => $tournamentTypeId,
                'team_id' => $s['team_id'],
                'rank' => $rank++,
            ]);
        }
    }

    public function swapTeams(Request $request, $matchId)
    {
        $match = Matches::find($matchId);
        if (!$match) {
            return ResponseHelper::error('Match not found', 404);
        }

        $validated = $request->validate([
            'home_team_id' => 'nullable|exists:teams,id',
            'away_team_id' => 'nullable|exists:teams,id',
        ]);

        // chỉ cho phép swap ở round 1 và khi chưa diễn ra
        if ($match->round != 1) {
            return ResponseHelper::error('Chỉ được hoán đổi đội ở Round 1.', 403);
        }
        if (!in_array($match->status, ['pending', 'not_started'])) {
            return ResponseHelper::error('Trận đã bắt đầu hoặc hoàn tất, không thể hoán đổi đội.', 403);
        }

        $targetTeamId = $validated['away_team_id'] ?? $validated['home_team_id'];
        if (!$targetTeamId) {
            return ResponseHelper::error('Thiếu team cần swap.', 400);
        }

        // Tìm trận chứa target team ở round 1
        $otherMatch = Matches::where('tournament_type_id', $match->tournament_type_id)
            ->where('round', 1)
            ->where('id', '<>', $match->id)
            ->where(function ($q) use ($targetTeamId) {
                $q->where('home_team_id', $targetTeamId)
                    ->orWhere('away_team_id', $targetTeamId);
            })
            ->first();

        if (!$otherMatch) {
            return ResponseHelper::error('Không tìm thấy trận chứa đội cần swap.', 404);
        }

        DB::transaction(function () use ($match, $otherMatch, $validated, $targetTeamId) {
            $swapIsHome = isset($validated['home_team_id']);

            // Xác định đội nào đang ở trận hiện tại cần bị thay thế
            $oldTeamToMove = $swapIsHome ? $match->home_team_id : $match->away_team_id;

            // Xác định vị trí của target team ở trận kia
            $targetIsHomeInOther = ($otherMatch->home_team_id == $targetTeamId);

            // Kiểm tra xem trận nào là bye
            $matchIsBye = ($match->home_team_id === null || $match->away_team_id === null);
            $otherMatchIsBye = ($otherMatch->home_team_id === null || $otherMatch->away_team_id === null);

            // Xác định đội nào đang có bye advantage
            $teamWithByeAdvantage = null;
            if ($matchIsBye) {
                $teamWithByeAdvantage = $match->home_team_id ?? $match->away_team_id;
            } elseif ($otherMatchIsBye) {
                $teamWithByeAdvantage = $otherMatch->home_team_id ?? $otherMatch->away_team_id;
            }

            // Bước 1: Thay đội ở trận hiện tại
            if ($swapIsHome) {
                $match->update(['home_team_id' => $targetTeamId]);
            } else {
                $match->update(['away_team_id' => $targetTeamId]);
            }

            // Bước 2: Đưa đội cũ vào vị trí của target team ở trận kia
            if ($targetIsHomeInOther) {
                $otherMatch->update(['home_team_id' => $oldTeamToMove]);
            } else {
                $otherMatch->update(['away_team_id' => $oldTeamToMove]);
            }

            // Bước 3: Cập nhật is_bye cho cả 2 trận
            $match->update([
                'is_bye' => ($match->home_team_id === null || $match->away_team_id === null),
            ]);
            $otherMatch->update([
                'is_bye' => ($otherMatch->home_team_id === null || $otherMatch->away_team_id === null),
            ]);

            // Bước 4: Nếu có đội có bye advantage, cập nhật tất cả các round sau
            if ($teamWithByeAdvantage) {
                // Tìm đội nào sẽ nhận bye advantage mới
                $newTeamWithBye = null;
                if ($match->is_bye) {
                    $newTeamWithBye = $match->home_team_id ?? $match->away_team_id;
                } elseif ($otherMatch->is_bye) {
                    $newTeamWithBye = $otherMatch->home_team_id ?? $otherMatch->away_team_id;
                }

                // Thay thế đội cũ có bye bằng đội mới trong tất cả các round sau
                if ($newTeamWithBye && $teamWithByeAdvantage != $newTeamWithBye) {
                    Matches::where('tournament_type_id', $match->tournament_type_id)
                        ->where('round', '>', 1)
                        ->where(function ($q) use ($teamWithByeAdvantage) {
                            $q->where('home_team_id', $teamWithByeAdvantage)
                                ->orWhere('away_team_id', $teamWithByeAdvantage);
                        })
                        ->get()
                        ->each(function ($m) use ($teamWithByeAdvantage, $newTeamWithBye) {
                            if ($m->home_team_id == $teamWithByeAdvantage) {
                                $m->update(['home_team_id' => $newTeamWithBye]);
                            }
                            if ($m->away_team_id == $teamWithByeAdvantage) {
                                $m->update(['away_team_id' => $newTeamWithBye]);
                            }
                        });
                }
            }

            // Reset kết quả & trạng thái cho round 1
            foreach ([$match, $otherMatch] as $m) {
                $m->update([
                    'winner_id' => null,
                    'status' => 'pending',
                ]);
                $m->results()->delete();
            }

            // Reset tất cả các trận từ round 2 trở đi
            Matches::where('tournament_type_id', $match->tournament_type_id)
                ->where('round', '>', 1)
                ->update([
                    'winner_id' => null,
                    'status' => 'pending',
                ]);
            Matches::where('tournament_type_id', $match->tournament_type_id)
                ->where('round', '>', 1)
                ->get()
                ->each(function ($m) {
                    $m->results()->delete();
                });
        });

        return ResponseHelper::success([
            'message' => 'Hoán đổi đội thành công',
            'match_1' => $match->fresh(),
            'match_2' => $otherMatch->fresh(),
        ]);
    }

    public function generateQr($matchId)
    {
        $match = Matches::findOrFail($matchId);
        $url = url("/api/matches/confirm-result/{$match->id}");

        return ResponseHelper::success(['qr_url' => $url], 'Thành công');
    }

    public function confirmResult($matchId)
    {
        $match = Matches::with(['results', 'tournamentType.tournament'])->findOrFail($matchId);
        $tournament = $match->tournamentType->tournament;
        $isOrganizer = $tournament->hasOrganizer(Auth::id());
        $teamIds = [$match->home_team_id, $match->away_team_id];
        $userTeam = Team::whereIn('id', $teamIds)
            ->whereHas('members', function($query) {
                $query->where('user_id', Auth::id());
            })
            ->first();
            
        if(!$userTeam && !$isOrganizer){
            return ResponseHelper::error('Bạn không có quyền xác nhận kết quả trận đấu này', 403);
        }
        if ($match->status === Matches::STATUS_COMPLETED) {
            return ResponseHelper::error('Kết quả trận đấu đã được xác nhận trước đó', 400);
        }
        if ($isOrganizer) {
            $match->home_team_confirm = true;
            $match->away_team_confirm = true;
        } else {
            if ($userTeam && $userTeam->id == $match->home_team_id) {
                $match->home_team_confirm = true;
            } elseif ($userTeam && $userTeam->id == $match->away_team_id) {
                $match->away_team_confirm = true;
            }
        }

        if($match->home_team_confirm && $match->away_team_confirm) {
            $match->status = Matches::STATUS_COMPLETED;
            foreach ($match->results as $result) {
                $result->confirmed = true;
            }
        }
        // Tính toán S cho từng team
        $scores = $match->results
                ->groupBy('team_id')
                ->map(fn($results) => $results->sum('score'));
        $homeScore = $scores->get($match->home_team_id, 0);
        $awayScore = $scores->get($match->away_team_id, 0);
        $totalScore = $homeScore + $awayScore;
        $S_home = $totalScore > 0 ? $homeScore / $totalScore : 0;
        $S_away = $totalScore > 0 ? $awayScore / $totalScore : 0;
        // Tính toán E cho từng team
        $sportId = $tournament->sport_id;
        // Hàm helper để lấy rating trung bình của team
        $getAverageRating = function($team, $sportId) {
            // Lấy tất cả thành viên của team
            $teamMembers = $team->members;
            if ($teamMembers->isEmpty()) {
                return 0;
            }
            
            $totalRating = 0;
            foreach ($teamMembers as $member) {
                $userSport = DB::table('user_sport')
                    ->where('user_id', $member->id)
                    ->where('sport_id', $sportId)
                    ->first();

                if ($userSport) {
                    $scoreRecord = DB::table('user_sport_scores')
                        ->where('user_sport_id', $userSport->id)
                        ->where('score_type', 'vndupr_score')
                        ->first();
                    
                    $totalRating += $scoreRecord ? (float) $scoreRecord->score_value : 0;
                }
            }
            
            return $totalRating / $teamMembers->count();
        };
        $homeTeamRating = $getAverageRating($match->homeTeam, $sportId);
        $awayTeamRating = $getAverageRating($match->awayTeam, $sportId);

        $E_home = 1 / (1 + pow(10, ($awayTeamRating - $homeTeamRating)));
        $E_away = 1 / (1 + pow(10, ($homeTeamRating - $awayTeamRating)));
        $teams = [
            $match->home_team_id => [
                'team' => $match->homeTeam,
                'S' => $S_home,
                'E' => $E_home,
            ],
            $match->away_team_id => [
                'team' => $match->awayTeam,
                'S' => $S_away,
                'E' => $E_away,
            ],
        ];
        
        $W = 0.2;
        
        foreach ($teams as $teamId => $data) {
            $team = $data['team'];
            $S = $data['S'];
            $E = $data['E'];
            
            // Lấy tất cả thành viên của team
            $teamMembers = $team->members;
            
            // Cập nhật điểm cho từng user trong team
            foreach ($teamMembers as $member) {
                $user = $member;
                $userId = $member->id;
                
                // 1. Tăng total_matches
                $user->total_matches = ($user->total_matches ?? 0) + 1;
                $user->save();
                
                // 2. Lấy R_old của user này
                $userSport = DB::table('user_sport')
                    ->where('user_id', $userId)
                    ->where('sport_id', $sportId)
                    ->first();
        
                $R_old = 0;
                if ($userSport) {
                    $scoreRecord = DB::table('user_sport_scores')
                        ->where('user_sport_id', $userSport->id)
                        ->where('score_type', 'vndupr_score')
                        ->first();
                    
                    $R_old = $scoreRecord ? (float) $scoreRecord->score_value : 0;
                }
                
                // 3. Lấy lịch sử 15 trận gần nhất
                $history = VnduprHistory::where('user_id', $userId)
                    ->orderByDesc('id')
                    ->take(15)
                    ->get()
                    ->sortBy('id')
                    ->values();
                
                // 4. Chuẩn bị K theo total_matches
                if ($user->total_matches <= 10) {
                    $K = 1;
                } elseif ($user->total_matches <= 50) {
                    $K = 0.6;
                } else {
                    $K = 0.3;
                }
                
                // 5. Kiểm tra TURBO
                if ($history->count() >= 2) {
                    $first_old = $history->first()->score_before;
                    $last_new = $history->last()->score_after;
                    
                    if (($first_old - $last_new) > 0.5) {
                        $K = 1; // bật chế độ turbo
                    }
                }
                
                // 6. Tính R_new
                $R_new = $R_old + ($W * $K * ($S - $E));
                
                // 7. Lưu history
                VnduprHistory::create([
                    'user_id' => $userId,
                    'match_id' => $match->id,
                    'mini_match_id' => null,
                    'score_before' => $R_old,
                    'score_after' => $R_new,
                ]);
                
                // 8. Update điểm vndupr_score vào user_sport_scores
                if ($userSport) {
                    $exists = DB::table('user_sport_scores')
                        ->where('user_sport_id', $userSport->id)
                        ->where('score_type', 'vndupr_score')
                        ->exists();
        
                    if ($exists) {
                        DB::table('user_sport_scores')
                            ->where('user_sport_id', $userSport->id)
                            ->where('score_type', 'vndupr_score')
                            ->update([
                                'score_value' => $R_new,
                                'updated_at'  => now(),
                            ]);
                    } else {
                        DB::table('user_sport_scores')->insert([
                            'user_sport_id' => $userSport->id,
                            'score_type'    => 'vndupr_score',
                            'score_value'   => $R_new,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]);
                    }
                }
            }
        }
        $match->save();

        return ResponseHelper::success(new MatchesResource($match->fresh('results')), 'Xác nhận kết quả thành công');
    }
}
