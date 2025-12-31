<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\MatchDetailResource;
use App\Http\Resources\MatchesResource;
use App\Models\Matches;
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
            'last_page' => $matches->lastPage(),
            'per_page' => $matches->perPage(),
            'total' => $matches->total(),
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
        $tournament = $match->tournamentType->tournament->load('staff');
        $isOrganizer = $tournament->hasOrganizer(Auth::id());

        if (!$isOrganizer) {
            return ResponseHelper::error('Bạn không có quyền thực hiện hành động này', 400);
        }

        if ($match->status === Matches::STATUS_COMPLETED || $match->home_team_confirm == 1 || $match->away_team_confirm == 1) {
            return ResponseHelper::error('Kết quả trận đấu đã được xác nhận không thể thay đổi điểm số', 400);
        }

        $match->update(['court' => $validated['court'] ?? $match->court]);

        $rules = $match->tournamentType->match_rules ?? null;
        if (!$rules) {
            return ResponseHelper::error('Thể thức này chưa có luật thi đấu (match_rules).', 400);
        }

        $setsPerMatch = $rules[0]['sets_per_match'] ?? 3;
        $pointsToWinSet = $rules[0]['points_to_win_set'] ?? 11;
        $winningRule = $rules[0]['winning_rule'] ?? 2; // cách biệt tối thiểu để win
        $maxPoints = $rules[0]['max_points'] ?? $pointsToWinSet;

        if (count($validated['results'] ?? []) > $setsPerMatch * 2) {
            return ResponseHelper::error("Số sets vượt quá giới hạn.", 400);
        }

        // 🔄 Gom dữ liệu theo từng set_number
        $sets = collect($validated['results'] ?? [])->groupBy('set_number');
        $keepIds = [];

        foreach ($sets as $setNumber => $setResults) {
            // chỉ xử lý khi có đủ 2 đội trong set
            if ($setResults->count() !== 2) {
                 return ResponseHelper::error("Set $setNumber thiếu kết quả của một đội. Vui lòng cung cấp điểm số cho cả hai đội.", 400);
            }

            $teamA = $setResults[0];
            $teamB = $setResults[1];
            $A = (int)$teamA['score'];
            $B = (int)$teamB['score'];

            $winnerTeamId = null;
            $isSetCompleted = false;

            // Kiểm tra điểm số không âm
            if ($A < 0 || $B < 0) {
                 return ResponseHelper::error("Điểm số không hợp lệ trong set $setNumber.", 400);
            }

            // 🧮 Xác định đội thắng set theo 3 quy tắc
            
            $scoreDiff = abs($A - $B);
            $isPointsToWinReached = ($A >= $pointsToWinSet || $B >= $pointsToWinSet);
            $isMaxPointsReached = ($A == $maxPoints || $B == $maxPoints);

            // 1. Trường hợp pointsToWinSet = maxPoints
            if ($pointsToWinSet == $maxPoints) {
                if ($isMaxPointsReached) {
                    $isSetCompleted = true;
                    $winnerTeamId = $A > $B ? $teamA['team_id'] : $teamB['team_id'];
                }
            } else {
                // Trường hợp pointsToWinSet != maxPoints

                // 2. Nếu đã chạm điểm pointsToWinSet và cách biệt winningRule điểm
                if ($isPointsToWinReached && $scoreDiff >= $winningRule) {
                    $isSetCompleted = true;
                    $winnerTeamId = $A > $B ? $teamA['team_id'] : $teamB['team_id'];
                } 
                // 3. Nếu chạm maxPoints (Luật "Deuce" kết thúc)
                elseif ($isMaxPointsReached) {
                    $isSetCompleted = true;
                    // Nếu điểm bằng nhau ở maxPoints, thì không thể kết thúc (lỗi dữ liệu)
                    if ($A == $B) {
                        return ResponseHelper::error("Điểm số hòa tại điểm tối đa $maxPoints trong set $setNumber. Set phải kết thúc với cách biệt.", 400);
                    }
                    $winnerTeamId = $A > $B ? $teamA['team_id'] : $teamB['team_id'];
                }
            }

            // 🚫 Yêu cầu: Chỉ lưu khi set đã hoàn thành (isSetCompleted = true)
            if (!$isSetCompleted) {
                return ResponseHelper::error("Set $setNumber có điểm số $A - $B chưa thỏa mãn luật thắng. Chỉ có thể lưu kết quả khi set đã hoàn thành.", 400);
            }
            
            // --- Bắt đầu kiểm tra tính hợp lệ của điểm cuối cùng ---
            
            $winningScore = max($A, $B);
            $losingScore = min($A, $B);

            // Nếu pointsToWinSet = maxPoints
            if ($pointsToWinSet == $maxPoints) {
                // Phải thắng tại điểm maxPoints
                if ($winningScore != $maxPoints ) {
                    return ResponseHelper::error("Điểm số $A - $B trong set $setNumber không hợp lệ với luật (thắng khi chạm $maxPoints).", 400);
                }
                if($losingScore == $maxPoints) {
                    return ResponseHelper::error("Điểm số $A - $B trong set $setNumber không hợp lệ với luật (không thể hòa tại $maxPoints).", 400);
                }
            }
            // Nếu pointsToWinSet != maxPoints
            else {
                // 1. Nếu set kết thúc bằng cách biệt >= winningRule trước maxPoints
                if ($winningScore < $maxPoints) {
                    if (!($winningScore >= $pointsToWinSet && ($winningScore - $losingScore) >= $winningRule)) {
                         return ResponseHelper::error("Điểm số $A - $B trong set $setNumber không hợp lệ với luật (trước $maxPoints).", 400);
                    }
                    for ($i = $pointsToWinSet; $i < $winningScore; $i++) {
                        // Tại mỗi điểm i, kiểm tra xem đã thắng chưa
                        $diffAtPoint = $i - $losingScore;
                        if ($diffAtPoint >= $winningRule) {
                            return ResponseHelper::error("Điểm số $A - $B trong set $setNumber không hợp lệ. Set kết thúc sớm hơn tại $i - $losingScore.", 400);
                        }
                    }
                } 
                // 2. Nếu set kết thúc tại maxPoints (ví dụ: 15-14)
                else {
                    if (!($winningScore == $maxPoints && $winningScore > $losingScore)) {
                        return ResponseHelper::error("Điểm số $A - $B trong set $setNumber không hợp lệ với luật (tại $maxPoints).", 400);
                    }
                    for ($i = $pointsToWinSet; $i < $maxPoints; $i++) {
                        $diffAtPoint = $i - $losingScore;
                        if ($diffAtPoint >= $winningRule) {
                            return ResponseHelper::error("Điểm số $A - $B trong set $setNumber không hợp lệ. Set kết thúc sớm hơn tại $i - $losingScore.", 400);
                        }
                    }
                }
            }

            // Phải có người thắng sau khi kiểm tra hợp lệ
            if (!$winnerTeamId) {
                 return ResponseHelper::error("Lỗi xác định người thắng trong set $setNumber.", 400);
            }

            // Lưu kết quả set đã hoàn thành
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
        $match->update([
            'home_team_confirm' => 0,
            'away_team_confirm' => 0,
        ]);

        $match->load('results');

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

        if ($match->next_match_id) {
            $nextMatch = Matches::find($match->next_match_id);
            if ($nextMatch) {
                if ($match->next_position === 'home') {
                    $nextMatch->update([
                        'home_team_id' => $winnerTeamId,
                        'status' => Matches::STATUS_PENDING,
                        'is_bye' => $nextMatch->away_team_id ? false : $nextMatch->is_bye,
                    ]);
                } elseif ($match->next_position === 'away') {
                    $nextMatch->update([
                        'away_team_id' => $winnerTeamId,
                        'status' => Matches::STATUS_PENDING,
                        'is_bye' => $nextMatch->home_team_id ? false : $nextMatch->is_bye,
                    ]);
                }
            }
        }
    
        // 🥉 Xử lý đội THUA vào trận tranh hạng 3 (nếu có)
        if ($match->loser_next_match_id) {
            // Xác định đội thua
            $loserTeamId = null;
            if ($match->home_team_id == $winnerTeamId) {
                $loserTeamId = $match->away_team_id;
            } elseif ($match->away_team_id == $winnerTeamId) {
                $loserTeamId = $match->home_team_id;
            }
    
            if ($loserTeamId) {
                $loserNextMatch = Matches::find($match->loser_next_match_id);
                if ($loserNextMatch) {
                    if ($match->loser_next_position === 'home') {
                        $loserNextMatch->update([
                            'home_team_id' => $loserTeamId,
                            'status' => Matches::STATUS_PENDING,
                        ]);
                    } elseif ($match->loser_next_position === 'away') {
                        $loserNextMatch->update([
                            'away_team_id' => $loserTeamId,
                            'status' => Matches::STATUS_PENDING,
                        ]);
                    }
                }
            }
        }
    }

    private function checkAndAdvanceFromPool($completedMatch)
    {
        $groupId = $completedMatch->group_id;
        if (!$groupId) return;
    
        $tournamentTypeId = $completedMatch->tournament_type_id;
        
        // 1. Kiểm tra xem tất cả các trận trong bảng đã xong chưa
        $allGroupMatches = Matches::where('group_id', $groupId)
            ->where('round', 1)
            ->get();
    
        $allCompleted = $allGroupMatches->every(fn($m) => $m->status === Matches::STATUS_COMPLETED);
        if (!$allCompleted) return;
    
        // 2. QUAN TRỌNG: Phải cập nhật lại Rank chuẩn theo Rule trước khi chọn đội đi tiếp
        $this->recalculateRankings($tournamentTypeId);
    
        // 3. Lấy bảng xếp hạng của các đội TRONG GROUP NÀY từ bảng TeamRanking
        // Chúng ta dựa vào việc team đó có thi đấu trong matches của Group này
        $teamIdsInGroup = $allGroupMatches->pluck('home_team_id')
            ->merge($allGroupMatches->pluck('away_team_id'))
            ->unique()
            ->filter();
    
        $standings = TeamRanking::where('tournament_type_id', $tournamentTypeId)
            ->whereIn('team_id', $teamIdsInGroup)
            ->orderBy('rank', 'asc') // Đội rank 1 (tổng) sẽ đứng đầu trong nhóm này
            ->get()
            ->values();
    
        // 4. Lấy luật tiến cử (Advancement Rules)
        $rules = PoolAdvancementRule::where('group_id', $groupId)
            ->orderBy('rank') // rank ở đây là vị trí trong bảng (1, 2...)
            ->get();
    
        if ($rules->isEmpty()) return;
    
        // ✅ Group rules theo rank để xử lý từng đội
        $rulesByRank = $rules->groupBy('rank');
        
        foreach ($rulesByRank as $rank => $rulesForRank) {
            // Lấy đội tương ứng với vị trí được quy định
            $teamAtPosition = $standings->get($rank - 1); 
    
            if (!$teamAtPosition) continue;
    
            // ✅ Cập nhật TẤT CẢ các legs của đội này
            foreach ($rulesForRank as $rule) {
                $nextMatch = Matches::find($rule->next_match_id);
                if (!$nextMatch) continue;
    
                $updateData = ['status' => Matches::STATUS_PENDING];
                if ($rule->next_position === 'home') {
                    $updateData['home_team_id'] = $teamAtPosition->team_id;
                } else {
                    $updateData['away_team_id'] = $teamAtPosition->team_id;
                }
    
                $nextMatch->update($updateData);
                
                // Nếu trận knockout này đủ 2 đội, có thể update status thành ready/pending
                if ($nextMatch->home_team_id && $nextMatch->away_team_id) {
                    $nextMatch->update(['status' => Matches::STATUS_PENDING]);
                }
            }
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
    
        $allCompleted = $allPoolMatches->every(fn($m) => $m->status === 'completed');
    
        if (!$allCompleted) {
            return;
        }
    
        // Tất cả pool đã hoàn thành
        $tournamentType = TournamentType::find($tournamentTypeId);
        if (!$tournamentType) {
            return;
        }
    
        $config = $tournamentType->format_specific_config ?? [];
        $mainConfig = is_array($config) && isset($config[0]) ? $config[0] : [];
        $advancedToNext = filter_var($mainConfig['advanced_to_next_round'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
        // Lấy tất cả trận knockout round 2 (vòng đấu đầu tiên sau pool)
        $knockoutMatches = Matches::where('tournament_type_id', $tournamentTypeId)
            ->where('round', 2)
            ->where('status', 'pending')
            ->get();
    
        if ($knockoutMatches->isEmpty()) {
            return;
        }
    
        // Tìm các trận có đội lẻ (is_bye = true hoặc có 1 team null)
        $byeMatches = $knockoutMatches->filter(function ($match) {
            return $match->is_bye || $match->home_team_id === null || $match->away_team_id === null;
        });
    
        if ($byeMatches->isEmpty()) {
            // Không có đội lẻ, tất cả đã sẵn sàng
            return;
        }
    
        if (!$advancedToNext) {
            // Nếu advanced_to_next_round = false, giữ nguyên bye
            // Các đội bye sẽ tự động đi tiếp
            return;
        }
    
        // advanced_to_next_round = true: Tìm best loser để đấu với đội lẻ
        $this->assignBestLosersToByeMatches($tournamentTypeId, $byeMatches);
    }
    
    private function assignBestLosersToByeMatches($tournamentTypeId, $byeMatches)
    {
        // 1. Lấy thông tin TournamentType và cấu hình ranking
        $tournamentType = TournamentType::find($tournamentTypeId);
        if (!$tournamentType) return;
    
        // Lấy config từ format_specific_config (Hỗ trợ cả dạng mảng bọc ngoài hoặc object trực tiếp)
        $config = $tournamentType->format_specific_config;
        if (is_array($config) && isset($config[0])) {
            $config = $config[0];
        }
        
        // Mảng các Rule ID (ví dụ: [1, 2, 3])
        $rankingRules = $config['ranking'] ?? [1, 3]; 
    
        // 2. Lấy tất cả các group
        $groups = DB::table('groups')
            ->join('matches', 'groups.id', '=', 'matches.group_id')
            ->where('matches.tournament_type_id', $tournamentTypeId)
            ->where('matches.round', 1)
            ->select('groups.id', 'groups.name')
            ->distinct()
            ->get();
    
        if ($groups->isEmpty()) return;
    
        // 3. Tính standings cho tất cả các group để tìm ứng viên
        $allGroupStandings = collect();
        foreach ($groups as $group) {
            $groupMatches = Matches::where('group_id', $group->id)
                ->where('round', 1)
                ->with(['homeTeam.members', 'awayTeam.members', 'results'])
                ->get();
    
            $standings = TournamentService::calculateGroupStandings($groupMatches);
            $advancementRules = PoolAdvancementRule::where('group_id', $group->id)->pluck('rank')->toArray();
            
            foreach ($standings as $index => $standing) {
                $rank = $index + 1;
                if (!in_array($rank, $advancementRules)) {
                    $allGroupStandings->push([
                        'team_id' => $standing['team']['id'],
                        'points' => $standing['points'] ?? 0,
                        'win_rate' => $standing['win_rate'] ?? 0, // Cần đảm bảo hàm calculateGroupStandings có trả về cái này
                        'sets_won' => $standing['sets_won'] ?? 0,
                        'points_won' => $standing['points_won'] ?? 0, // Tổng điểm ghi được (không phải point BXH)
                        // Thêm các trường khác nếu cần để map với hằng số
                    ]);
                }
            }
        }
    
        if ($allGroupStandings->isEmpty()) return;
    
        // 4. Sắp xếp Best Losers dựa trên mảng Ranking trong Config
        $bestLosers = $allGroupStandings->sort(function ($a, $b) use ($rankingRules) {
            foreach ($rankingRules as $ruleId) {
                $field = null;
                
                // Map từ Const sang key trong mảng $standing
                switch ((int)$ruleId) {
                    case 1: // RANKING_WIN_DRAW_LOSE_POINTS
                        $field = 'points';
                        break;
                    case 2: // RANKING_WIN_RATE
                        $field = 'win_rate';
                        break;
                    case 3: // RANKING_SETS_WON
                        $field = 'sets_won';
                        break;
                    case 4: // RANKING_POINTS_WON
                        $field = 'points_won';
                        break;
                    // Rule 5 (Head-to-head) bỏ qua khi so sánh giữa các bảng khác nhau
                    // Rule 6 (Random) xử lý sau cùng nếu cần
                }
    
                if ($field && isset($a[$field], $b[$field])) {
                    if ($a[$field] != $b[$field]) {
                        return $b[$field] <=> $a[$field]; // Sắp xếp giảm dần
                    }
                }
            }
            return 0;
        })->values();
    
        // 5. Gán best losers vào các trận bye
        $loserIndex = 0;
        foreach ($byeMatches as $byeMatch) {
            if ($loserIndex >= $bestLosers->count()) break;
    
            $bestLoser = $bestLosers[$loserIndex];
            
            $updateData = [];
            if ($byeMatch->home_team_id === null) {
                $updateData = ['home_team_id' => $bestLoser['team_id']];
            } elseif ($byeMatch->away_team_id === null) {
                $updateData = ['away_team_id' => $bestLoser['team_id']];
            }
    
            if (!empty($updateData)) {
                $updateData['is_bye'] = false;
                $updateData['status'] = Matches::STATUS_PENDING; // Nên dùng Const
                $byeMatch->update($updateData);
                $loserIndex++;
            }
        }
    }

    private function recalculateRankings($tournamentTypeId)
    {
        $tournamentType = TournamentType::find($tournamentTypeId);
        if (!$tournamentType) return;
    
        // Ép kiểu mảng ranking rules về Integer ngay từ đầu để tránh lỗi switch-case
        $config = $tournamentType->format_specific_config ?? [];
        $rankingRules = collect($config['ranking'] ?? [1, 2])->map(fn($id) => (int)$id)->toArray();
    
        $tournament_id = $tournamentType->tournament_id;
    
        // 1️⃣ Lấy danh sách teams
        $teams = Team::where('tournament_id', $tournament_id)->select('id')->distinct()->get();
        if ($teams->isEmpty()) return;
    
        // 2️⃣ Khởi tạo mảng thống kê
        $stats = [];
        foreach ($teams as $team) {
            $stats[$team->id] = [
                'team_id'    => $team->id,
                'played'     => 0,
                'wins'       => 0,
                'losses'     => 0,
                'points'     => 0,
                'sets_won'   => 0,
                'sets_lost'  => 0,
                'points_won' => 0,
                'points_lost'=> 0,
                'set_diff'   => 0,
                'point_diff' => 0,
                'win_rate'   => 0,
            ];
        }
    
        // 3️⃣ Lấy dữ liệu trận đấu đã hoàn thành
        $matches = Matches::where('tournament_type_id', $tournamentTypeId)
            ->where('status', 'completed')
            ->with('results')
            ->get();
    
        foreach ($matches as $match) {
            $home = $match->home_team_id;
            $away = $match->away_team_id;
            $winner = $match->winner_id;
            $loser = ($winner == $home) ? $away : (($winner == $away) ? $home : null);
    
            foreach ([$home, $away] as $tid) {
                if ($tid && isset($stats[$tid])) {
                    $stats[$tid]['played']++;
                }
            }
    
            if ($winner && $loser && isset($stats[$winner]) && isset($stats[$loser])) {
                $stats[$winner]['wins']++;
                $stats[$winner]['points'] += 3; // Hoặc tùy chỉnh điểm số của bạn
                $stats[$loser]['losses']++;
            }
    
            foreach ($match->results as $r) {
                if (isset($stats[$r->team_id])) {
                    $stats[$r->team_id]['points_won'] += $r->score;
                    if ($r->won_match) {
                        $stats[$r->team_id]['sets_won']++;
                    } else {
                        $stats[$r->team_id]['sets_lost']++;
                    }
                }
            }
    
            // Tính points_lost để tính point_diff
            if ($home && $away && isset($stats[$home]) && isset($stats[$away])) {
                $homeScore = $match->results->where('team_id', $home)->sum('score');
                $awayScore = $match->results->where('team_id', $away)->sum('score');
                $stats[$home]['points_lost'] += $awayScore;
                $stats[$away]['points_lost'] += $homeScore;
            }
        }
    
        // 4️⃣ Tính toán các chỉ số phụ
        foreach ($stats as &$s) {
            $s['set_diff'] = $s['sets_won'] - $s['sets_lost'];
            $s['point_diff'] = $s['points_won'] - $s['points_lost'];
            $s['win_rate'] = $s['played'] > 0 ? round($s['wins'] / $s['played'] * 100, 2) : 0;
        }
        unset($s);
    
        // 5️⃣ Sắp xếp linh hoạt theo Ranking Rules
        $sorted = collect($stats)->sort(function ($a, $b) use ($rankingRules, $matches) {
            // Đội đã đánh luôn đứng trên đội chưa đánh
            if ($a['played'] == 0 && $b['played'] > 0) return 1;
            if ($b['played'] == 0 && $a['played'] > 0) return -1;
    
            foreach ($rankingRules as $ruleId) {
                switch ($ruleId) {
                    case TournamentType::RANKING_WIN_DRAW_LOSE_POINTS: // Rule 1
                        if ($a['points'] !== $b['points']) return $b['points'] <=> $a['points'];
                        break;
                    case TournamentType::RANKING_WIN_RATE: // Rule 2
                        if ($a['win_rate'] !== $b['win_rate']) return $b['win_rate'] <=> $a['win_rate'];
                        break;
                    case TournamentType::RANKING_SETS_WON: // Rule 3
                        if ($a['set_diff'] !== $b['set_diff']) return $b['set_diff'] <=> $a['set_diff'];
                        break;
                    case TournamentType::RANKING_POINTS_WON: // Rule 4
                        if ($a['point_diff'] !== $b['point_diff']) return $b['point_diff'] <=> $a['point_diff'];
                        break;
                    case TournamentType::RANKING_HEAD_TO_HEAD: // Rule 5
                        $h2h = $this->getHeadToHeadResult($a['team_id'], $b['team_id'], $matches);
                        if ($h2h !== 0) return $h2h;
                        break;
                    case TournamentType::RANKING_RANDOM_DRAW: // Rule 6
                        return $a['team_id'] <=> $b['team_id'];
                }
            }
            
            // Cầu chì cuối cùng: Nếu tất cả các luật cài đặt đều bằng nhau,
            // mặc định lấy Hiệu số điểm (Point Diff) để phân định, sau đó mới đến ID.
            if ($a['point_diff'] !== $b['point_diff']) return $b['point_diff'] <=> $a['point_diff'];
            return $a['team_id'] <=> $b['team_id'];
    
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
    
    /**
     * So sánh đối đầu giữa 2 đội
     * Return: -1 nếu team A thắng, 1 nếu team B thắng, 0 nếu hòa hoặc chưa gặp
     */
    private function getHeadToHeadResult($teamA, $teamB, $matches)
    {
        $h2hMatches = $matches->filter(function ($match) use ($teamA, $teamB) {
            return ($match->home_team_id == $teamA && $match->away_team_id == $teamB) ||
                   ($match->home_team_id == $teamB && $match->away_team_id == $teamA);
        });
    
        if ($h2hMatches->isEmpty())
            return 0;
    
        $teamAWins = 0;
        $teamBWins = 0;
    
        foreach ($h2hMatches as $match) {
            if ($match->winner_id == $teamA)
                $teamAWins++;
            elseif ($match->winner_id == $teamB)
                $teamBWins++;
        }
    
        if ($teamAWins > $teamBWins)
            return -1;
        elseif ($teamBWins > $teamAWins)
            return 1;
    
        return 0;
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
        if (!in_array($match->status, haystack: ['pending', 'not_started'])) {
            return ResponseHelper::error('Trận đã bắt đầu hoặc hoàn tất, không thể hoán đổi đội.', 403);
        }
        $tournamentType = TournamentType::find($match->tournament_type_id);
        if (in_array($tournamentType->format, [TournamentType::FORMAT_ROUND_ROBIN]) && $match->round == 1) {
            return ResponseHelper::error('Cài đặt thể thức không cho phép hoán đổi các đội đấu vòng tròn (round robin).', 403);
        }
        if ( $tournamentType->format === TournamentType::FORMAT_MIXED && $match->group && $match->round == 1) {
            return $this->handleMixedSwap($request, $match, $tournamentType);
        }        

        // chỉ cho phép swap ở round 1 và khi chưa diễn ra
        if ($match->round != 1) {
            return ResponseHelper::error('Chỉ được hoán đổi đội ở Round 1.', 403);
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
            return ResponseHelper::error('Có lỗi xảy ra khi đổi đội.', 404);
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

    private function handleMixedSwap(Request $request, Matches $match, TournamentType $tournamentType)
    {
        $validated = $request->validate([
            'from_team_id' => 'required|exists:teams,id',
            'to_team_id'   => 'required|exists:teams,id',
        ]);

        $fromTeamId = $validated['from_team_id'];
        $toTeamId   = $validated['to_team_id'];

        // 🚫 Cùng bảng thì cấm
        $sameGroup = Matches::where('tournament_type_id', $tournamentType->id)
            ->where('round', 1)
            ->where(function ($q) use ($fromTeamId, $toTeamId) {
                $q->where(function ($q) use ($fromTeamId) {
                    $q->where('home_team_id', $fromTeamId)
                        ->orWhere('away_team_id', $fromTeamId);
                });
            })
            ->where(function ($q) use ($toTeamId) {
                $q->where('home_team_id', $toTeamId)
                    ->orWhere('away_team_id', $toTeamId);
            })
            ->exists();

        if ($sameGroup) {
            return ResponseHelper::error(
                'Không cho phép hoán đổi đội trong cùng bảng của thể thức mixed.',
                403
            );
        }

        // ✅ Swap GLOBAL toàn bộ round 1
        DB::transaction(function () use ($tournamentType, $fromTeamId, $toTeamId) {

            $matches = Matches::where('tournament_type_id', $tournamentType->id)
                ->where('round', 1)
                ->where(function ($q) use ($fromTeamId, $toTeamId) {
                    $q->whereIn('home_team_id', [$fromTeamId, $toTeamId])
                        ->orWhereIn('away_team_id', [$fromTeamId, $toTeamId]);
                })
                ->lockForUpdate()
                ->get();

            foreach ($matches as $m) {

                if ($m->home_team_id == $fromTeamId) {
                    $m->home_team_id = $toTeamId;
                } elseif ($m->home_team_id == $toTeamId) {
                    $m->home_team_id = $fromTeamId;
                }

                if ($m->away_team_id == $fromTeamId) {
                    $m->away_team_id = $toTeamId;
                } elseif ($m->away_team_id == $toTeamId) {
                    $m->away_team_id = $fromTeamId;
                }

                $m->update([
                    'is_bye' => ($m->home_team_id === null || $m->away_team_id === null),
                    'winner_id' => null,
                    'status' => 'pending',
                ]);

                $m->results()->delete();
            }

            // reset các round sau
            Matches::where('tournament_type_id', $tournamentType->id)
                ->where('round', '>', 1)
                ->update([
                    'winner_id' => null,
                    'status' => 'pending',
                ]);

            Matches::where('tournament_type_id', $tournamentType->id)
                ->where('round', '>', 1)
                ->get()
                ->each(fn($m) => $m->results()->delete());
        });

        return ResponseHelper::success(null, 'Đã hoán đổi toàn bộ các trận đấu giữa hai đội ở hai bảng khác nhau.', 200);
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
        $tournament = $match->tournamentType->tournament->load('staff');
        $isOrganizer = $tournament->hasOrganizer(Auth::id());
        $teamIds = [$match->home_team_id, $match->away_team_id];
        $userTeam = Team::whereIn('id', $teamIds)
            ->whereHas('members', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->first();
        $rules = $match->tournamentType->match_rules ?? null;
        if (!$rules) {
            return ResponseHelper::error('Thể thức này chưa có luật thi đấu (match_rules).', 400);
        }
        $setsPerMatch = $rules[0]['sets_per_match'] ?? 3;
        $neededToWin = intdiv($setsPerMatch, 2) + 1;
        
        $sets = $match->results->groupBy('set_number');
        
        $wins = [];
        
        foreach ($sets as $setNumber => $setResults) {
            if ($setResults->count() < 2) {
                continue;
            }
        
            $sorted = $setResults->sortByDesc('score')->values();
        
            if ($sorted[0]->score !== $sorted[1]->score) {
                $winnerTeamId = $sorted[0]->team_id;
                $wins[$winnerTeamId] = ($wins[$winnerTeamId] ?? 0) + 1;
            }
        }
        
        $maxWin = max($wins ?: [0]);
        
        if ($maxWin < $neededToWin) {
            return ResponseHelper::error("Cần thắng tối thiểu $neededToWin set mới được xác nhận kết quả.",400);
        }        
        if (!$userTeam && !$isOrganizer) {
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

        if ($match->home_team_confirm && $match->away_team_confirm) {
            $match->status = Matches::STATUS_COMPLETED;
            foreach ($match->results as $result) {
                $result->confirmed = true;
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
            $getAverageRating = function ($team, $sportId) {
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

            $W = 0.6;

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
                                    'updated_at' => now(),
                                ]);
                        } else {
                            DB::table('user_sport_scores')->insert([
                                'user_sport_id' => $userSport->id,
                                'score_type' => 'vndupr_score',
                                'score_value' => $R_new,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
            $this->checkAndAdvanceFromMultiLeg($match, $setsPerMatch);
        }
        $match->save();

        return ResponseHelper::success(new MatchesResource($match->fresh('results')), 'Xác nhận kết quả thành công');
    }

    private function checkAndAdvanceFromMultiLeg($match, $setsPerMatch)
    {
        $tournamentType = $match->tournamentType;
        $numLegs = $tournamentType->num_legs ?? 1;
        
        // ========================================
        // BƯỚC 1: Xử lý trường hợp chỉ có 1 leg
        // ========================================
        if ($numLegs == 1) {
            $this->calculateMatchWinner($match, $setsPerMatch);
            return;
        }
        // Tính winner cho leg này trước
        $this->calculateMatchWinner($match, $setsPerMatch);
        // ========================================
        // BƯỚC 3: Lấy TẤT CẢ các legs của cặp đấu này
        // ========================================
        $allLegs = Matches::where('tournament_type_id', $match->tournament_type_id)
            ->where('round', $match->round)
            ->where(function($q) use ($match) {
                $q->where(function($q2) use ($match) {
                    // Trường hợp leg 1: A vs B
                    $q2->where('home_team_id', $match->home_team_id)
                       ->where('away_team_id', $match->away_team_id);
                })->orWhere(function($q2) use ($match) {
                    // Trường hợp leg 2: B vs A (đổi sân)
                    $q2->where('home_team_id', $match->away_team_id)
                       ->where('away_team_id', $match->home_team_id);
                });
            })->with('results')->get();
        // ========================================
        // BƯỚC 4: Kiểm tra tất cả legs đã hoàn thành chưa
        // ========================================
        $allCompleted = $allLegs->every(fn($m) => $m->status === Matches::STATUS_COMPLETED);
        
        if (!$allCompleted) {
            return;
        }
        // ========================================
        // BƯỚC 5: Tính tổng điểm aggregate từ TẤT CẢ các legs
        // ========================================
        // Xác định team gốc (theo leg đầu tiên hoặc match hiện tại)
        $homeTeamId = $match->home_team_id;
        $awayTeamId = $match->away_team_id;
        
        // Nếu là leg 2 (đã đổi sân), lấy theo thứ tự gốc
        if ($match->leg == 2) {
            // Tìm leg 1 để lấy thứ tự team gốc
            $leg1 = $allLegs->firstWhere('leg', 1);
            if ($leg1) {
                $homeTeamId = $leg1->home_team_id;
                $awayTeamId = $leg1->away_team_id;
            }
        }
        
        $homeSetWins = 0;
        $awaySetWins = 0;
        
        foreach ($allLegs as $leg) {
            $legHomeId = $leg->home_team_id;
            $legAwayId = $leg->away_team_id;
            
            foreach ($leg->results->groupBy('set_number') as $setNumber => $setResults) {
                if ($setResults->count() < 2) {
                    continue;
                }
                
                $homeResult = $setResults->firstWhere('team_id', $legHomeId);
                $awayResult = $setResults->firstWhere('team_id', $legAwayId);
                
                if (!$homeResult || !$awayResult) {
                    continue;
                }
                
                $homeScore = (int) $homeResult->score;
                $awayScore = (int) $awayResult->score;

                // Xác định người thắng set này
                if ($homeScore > $awayScore) {
                    // Team home của leg này thắng set
                    if ($legHomeId == $homeTeamId) {
                        $homeSetWins++;
                    } else {
                        $awaySetWins++;
                    }
                } elseif ($awayScore > $homeScore) {
                    // Team away của leg này thắng set
                    if ($legAwayId == $homeTeamId) {
                        $homeSetWins++;
                    } else {
                        $awaySetWins++;
                    }
                }
            }
        }
        
        // ========================================
        // BƯỚC 6: Xác định winner CUỐI CÙNG
        // ========================================
        $finalWinnerId = null;
        if ($homeSetWins > $awaySetWins) {
            $finalWinnerId = $homeTeamId;
        } elseif ($awaySetWins > $homeSetWins) {
            $finalWinnerId = $awayTeamId;
        } else {
            return;
        }
        
        if (!$finalWinnerId) {
            return;
        }
        
        // ========================================
        // BƯỚC 7: Cập nhật winner_id cho TẤT CẢ các legs
        // ========================================
        foreach ($allLegs as $leg) {
            if ($leg->winner_id !== $finalWinnerId) {
                $leg->update(['winner_id' => $finalWinnerId]);
            }
        }
        
        // ========================================
        // BƯỚC 8: Tiến đội thắng vào vòng sau
        // ========================================
        if (in_array($match->tournamentType->format, [
            TournamentType::FORMAT_MIXED,
            TournamentType::FORMAT_ELIMINATION,
        ])) {
            if ($numLegs == 1) {
                // Nếu chỉ có 1 lượt: Chạy logic cũ đang hoạt động tốt của bạn
                $this->advanceWinnerToNextRound($match, $finalWinnerId);
            } else {
                // Nếu có từ 2 lượt trở lên: Chạy logic mới để điền vào cả 2 trận (Leg 1 & Leg 2)
                $this->syncWinnerToNextRoundLegs($match, $finalWinnerId);
            }
        }
        
        // ========================================
        // BƯỚC 9: Cập nhật bảng xếp hạng
        // ========================================
        $this->recalculateRankings($match->tournament_type_id);
    }

    private function syncWinnerToNextRoundLegs($match, $finalWinnerId)
    {
        $nextMatchId = $match->next_match_id;
        $nextPosition = $match->next_position; // 'home' hoặc 'away'

        if (!$nextMatchId || !$finalWinnerId) return;

        // 1. Tìm trận đấu đích được trỏ tới
        $targetMatch = Matches::find($nextMatchId);
        if (!$targetMatch) return;

        // 2. Lấy TẤT CẢ các legs của cặp đấu đó ở vòng sau (dựa vào tên và vòng)
        $nextRoundLegs = Matches::where('tournament_type_id', $match->tournament_type_id)
            ->where('round', $targetMatch->round)
            ->where('name_of_match', $targetMatch->name_of_match)
            ->get();

        foreach ($nextRoundLegs as $nextLeg) {
            $updateData = [];
            
            if ($nextPosition === 'home') {
                // Lượt đi (Leg lẻ): Bạn là Home | Lượt về (Leg chẵn): Bạn là Away
                if ($nextLeg->leg % 2 !== 0) {
                    $updateData['home_team_id'] = $finalWinnerId;
                } else {
                    $updateData['away_team_id'] = $finalWinnerId;
                }
            } else {
                // Lượt đi (Leg lẻ): Bạn là Away | Lượt về (Leg chẵn): Bạn là Home
                if ($nextLeg->leg % 2 !== 0) {
                    $updateData['away_team_id'] = $finalWinnerId;
                } else {
                    $updateData['home_team_id'] = $finalWinnerId;
                }
            }

            $nextLeg->update($updateData);
        }
    }
}
