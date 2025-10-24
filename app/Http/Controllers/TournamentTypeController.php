<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\TournamentTypeResource;
use App\Models\Matches;
use App\Models\TournamentType;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TournamentTypeController extends Controller
{
    /**
     * Tạo mới một thể thức cho giải đấu
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tournament_id' => 'required|integer|exists:tournaments,id',
            'format' => 'required|integer|in:' . implode(',', TournamentType::FORMATS),
            'num_legs' => 'integer|nullable|in:' . implode(',', TournamentType::NUM_LEGS_OPTIONS),
            'match_rules' => 'array|nullable',
            'format_specific_config' => 'array|nullable',
            'rules' => 'string|nullable',
            'rules_file_path' => 'string|nullable',
        ]);

        $poolStage = $validated['format_specific_config']['pool_stage'] ?? null;
        if ($poolStage) {
            $numCompeting = (int) ($poolStage['number_competing_teams'] ?? 0);
            $numAdvancing = (int) ($poolStage['num_advancing_teams'] ?? 0);

            if ($numCompeting < $numAdvancing) {
                return ResponseHelper::error('Số đội trong bảng phải > số đội đi tiếp', 422);
            }
        }

        $matchRules = $validated['match_rules'] ?? [];
        if($matchRules) {
            $setPerMatch = (int) ($matchRules['sets_per_match'] ?? 0);
            $winningRule = (int) ($matchRules['winning_rule'] ?? 0);
            if($setPerMatch > 0 && $winningRule > 0) {
                if($winningRule > $setPerMatch) {
                    return ResponseHelper::error('Quy tắc thắng phải nhỏ hơn số set trong trận', 422);
                }
            }
        }

        DB::beginTransaction();
        try {
            if ($request->hasFile('rules_file_path')) {
                $path = $request->file('rules_file_path')->store('tournament_rules', 'public');
                $validated['rules_file_path'] = $path;
            }
            $type = TournamentType::createWithFormat(
                $validated['tournament_id'],
                $validated['format'],
                [
                    'match_rules' => $validated['match_rules'] ?? [],
                    'format_specific_config' => $validated['format_specific_config'] ?? [],
                    'rules_file_path' => $validated['rules_file_path'] ?? null,
                ]
            );

            // rules không được create trực tiếp trong createWithFormat => set sau
            if (array_key_exists('rules', $validated)) {
                $type->rules = $validated['rules'];
                $type->save();
            }
            if (array_key_exists('num_legs', $validated)) {
                $type->num_legs = $validated['num_legs'];
                $type->save();
            }

            $this->generateMatchesForType($type);

            DB::commit();
            return ResponseHelper::success(new TournamentTypeResource($type), 'Tạo thể thức thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseHelper::error('Lỗi khi tạo thể thức: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cập nhật thông tin & thông số thể thức hiện tại
     * - Chỉ merge thay đổi, không reset toàn bộ (trừ khi explicit)
     */
    public function update(Request $request, TournamentType $tournamentType)
    {
        $validated = $request->validate([
            'match_rules' => 'array|nullable',
            'format_specific_config' => 'array|nullable',
            'rules' => 'string|nullable',
            'rules_file_path' => 'string|nullable',
            'num_legs' => 'integer|nullable|in:' . implode(',', TournamentType::NUM_LEGS_OPTIONS),
        ]);

        $poolStage = $validated['format_specific_config']['pool_stage'] ?? null;
        if ($poolStage) {
            $numCompeting = (int) ($poolStage['number_competing_teams'] ?? 0);
            $numAdvancing = (int) ($poolStage['num_advancing_teams'] ?? 0);

            if ($numCompeting < $numAdvancing) {
                return ResponseHelper::error('Số đội trong bảng phải > số đội đi tiếp', 422);
            }
        }

        $matchRules = $validated['match_rules'] ?? [];
        if($matchRules) {
            $setPerMatch = (int) ($matchRules['sets_per_match'] ?? 0);
            $winningRule = (int) ($matchRules['winning_rule'] ?? 0);
            if($setPerMatch > 0 && $winningRule > 0) {
                if($winningRule > $setPerMatch) {
                    return ResponseHelper::error('Quy tắc thắng phải nhỏ hơn số set trong trận', 422);
                }
            }
        }

        DB::beginTransaction();
        try {
            // Xoá toàn bộ cấu hình cũ trước khi cập nhật (đảm bảo không lẫn dữ liệu cũ)
            $tournamentType->match_rules = [];
            $tournamentType->format_specific_config = [];

            // Ghi đè hoàn toàn config mới (nếu có)
            if (!empty($validated['match_rules'])) {
                $tournamentType->match_rules = $validated['match_rules'];
            }

            if (!empty($validated['format_specific_config'])) {
                $tournamentType->format_specific_config = $validated['format_specific_config'];
            }

            // Ghi đè rules và file path
            if (array_key_exists('rules', $validated)) {
                $tournamentType->rules = $validated['rules'];
            }

            if ($request->hasFile('rules_file_path')) {
                $path = $request->file('rules_file_path')->store('tournament_rules', 'public');
                $tournamentType->rules_file_path = $path;
            } elseif (array_key_exists('rules_file_path', $validated)) {
                $tournamentType->rules_file_path = $validated['rules_file_path'];
            }

            // Ghi đè số lượt đấu
            if (array_key_exists('num_legs', $validated)) {
                $tournamentType->num_legs = $validated['num_legs'];
            }

            $tournamentType->save();

            $this->generateMatchesForType($tournamentType);

            DB::commit();
            return ResponseHelper::success(new TournamentTypeResource($tournamentType->fresh()), 'Cập nhật thể thức thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseHelper::error('Lỗi khi cập nhật thể thức: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Xem thông tin thể thức
     */
    public function show(TournamentType $tournamentType)
    {
        return ResponseHelper::success(new TournamentTypeResource($tournamentType));
    }

    /**
     * Xoá thể thức
     */
    public function destroy(TournamentType $tournamentType)
    {
        $tournamentType->matches()->each(function ($match) {
            $match->results()->delete();
            $match->delete();
        });        
        $tournamentType->teamRankings()->delete();
        if($tournamentType->format == 1) {
            $tournamentType->groups()->delete();
        }
        $tournamentType->delete();

        return ResponseHelper::success('Xoá thể thức thành công');
    }

    protected function generateMatchesForType(TournamentType $type)
    {
        $type->matches()->delete();
        $teams = $type->tournament->teams()->with('members')->get();
        if (count($teams) < 2) {
            return;
        }

        $config = $type->format_specific_config ?? [];
        $numLegs = $type->num_legs ?? 1;

        // Chọn branch theo format
        switch ($type->format) {
            case TournamentType::FORMAT_ROUND_ROBIN:
                $this->generateRoundRobin($type, $teams, $numLegs);
                break;

            case TournamentType::FORMAT_ELIMINATION:
                $this->generateElimination($type, $teams, $config, $numLegs);
                break;

            case TournamentType::FORMAT_MIXED:
            default:
                $this->generateMixed($type, $teams, $config, $numLegs);
                break;
        }
    }

    private function generateRoundRobin(TournamentType $type, $teams, $numLegs)
    {
        $teamCount = count($teams);
        if ($teamCount < 2) return;

        for ($i = 0; $i < $teamCount; $i++) {
            for ($j = $i + 1; $j < $teamCount; $j++) {
                for ($leg = 1; $leg <= $numLegs; $leg++) {
                    $isReturnLeg = ($leg % 2 == 0);
                    $home = $isReturnLeg ? $teams[$j]->id : $teams[$i]->id;
                    $away = $isReturnLeg ? $teams[$i]->id : $teams[$j]->id;
                    $type->matches()->create([
                        'home_team_id' => $home,
                        'away_team_id' => $away,
                        'tournament_type_id' => $type->id,
                        'leg' => $leg,
                    ]);
                }
            }
        }
    }

    private function generateElimination(TournamentType $type, $teams, $config, $numLegs)
    {
        $teamCount = count($teams);
        if ($teamCount < 2) return;

        $seedingRules = $config['seeding_rules'] ?? [];
        $byeSelectionOrder = $config['advanced_to_next_round'] ?? false;

        // -------------------------------
        // STEP 1: Seeding (giữ nguyên logic cũ)
        // -------------------------------
        foreach ($seedingRules as $rule) {
            switch ($rule) {
                case TournamentType::SEED_LEVEL:
                    $sportId = $type->tournament->sport_id ?? null;
                    $allUserIds = collect($teams)
                        ->flatMap(fn($team) => collect($team->members)->pluck('user_id'))
                        ->filter()->unique()->values()->all();
                    if (!$sportId || empty($allUserIds)) { $teams = $teams->shuffle()->values(); break; }

                    $userScores = DB::table('user_sports as us')
                        ->join('user_sport_scores as uss','us.id','=','uss.user_sport_id')
                        ->where('us.sport_id',$sportId)
                        ->where('uss.score_type','vndupr_score')
                        ->whereIn('us.user_id',$allUserIds)
                        ->pluck('uss.score_value','us.user_id')
                        ->map(fn($v)=>(float)$v)
                        ->toArray();

                    $teams = $teams->map(function($team) use ($userScores){
                        $userIds = collect($team->members)->pluck('user_id')->filter();
                        $scores = $userIds->map(fn($uid)=>$userScores[$uid]??0)->toArray();
                        $team->_seed_meta = [
                            'level'=>count($scores)?array_sum($scores)/count($scores):0,
                            '_last_match_goals'=>0
                        ];
                        return $team;
                    })->sortByDesc(fn($t)=>$t->_seed_meta['level'])->values();
                    break;

                case TournamentType::SEED_SAME_CLUB_AVOID:
                    $byClub = $teams->groupBy(fn($t)=>$t->club_id??'no_club');
                    $interleaved = collect();
                    while($byClub->isNotEmpty()){
                        foreach($byClub as $club=>$arr){
                            if($arr->isNotEmpty()) $interleaved->push($arr->shift());
                            if($arr->isEmpty()) $byClub->forget($club); else $byClub->put($club,$arr);
                        }
                    }
                    $teams = $interleaved->values();
                    break;

                default: $teams = $teams->shuffle()->values(); break;
            }
        }

        // -------------------------------
        // STEP 2: Generate bracket (với numLegs)
        // -------------------------------
        $round = 1;
        $currentTeams = $teams->values()->all();
        $matchMap = [];

        while(count($currentTeams) > 1){
            $nextRoundTeams = [];
            $roundMatches = [];
            $numTeams = count($currentTeams);
            $hasOdd = $numTeams % 2 !== 0;

            // tách đội lẻ
            $byeTeam = $hasOdd ? array_pop($currentTeams) : null;

            // tạo match cho các cặp
            for($i=0;$i<count($currentTeams);$i+=2){
                $home = $currentTeams[$i];
                $away = $currentTeams[$i+1] ?? null;

                for ($leg = 1; $leg <= $numLegs; $leg++) {
                    $isReturn = ($leg % 2 === 0);
                    $match = $type->matches()->create([
                        'tournament_type_id'=>$type->id,
                        'home_team_id'=> $isReturn ? ($away->id??null) : ($home->id??null),
                        'away_team_id'=> $isReturn ? ($home->id??null) : ($away->id??null),
                        'round'=>$round,
                        'leg'=>$leg,
                        'is_bye'=>false
                    ]);
                    $roundMatches[] = (object)[
                        'id'=>$match->id,
                        'home'=>$home,
                        'away'=>$away,
                        'winner'=>null,
                        'loser'=>null
                    ];
                }

                $nextRoundTeams[] = (object)['id'=>null,'_from_match_id'=>$roundMatches[0]->id];
            }

            // xử lý đội bye
            if($byeTeam){
                if($round===1 || !$byeSelectionOrder){
                    for ($leg = 1; $leg <= $numLegs; $leg++) {
                        $match = $type->matches()->create([
                            'tournament_type_id'=>$type->id,
                            'home_team_id'=>$byeTeam->id,
                            'away_team_id'=>null,
                            'round'=>$round,
                            'leg'=>$leg,
                            'is_bye'=>true
                        ]);
                        $roundMatches[] = (object)[
                            'id'=>$match->id,
                            'home'=>$byeTeam,
                            'away'=>null,
                            'winner'=>null,
                            'loser'=>null
                        ];
                    }
                    $nextRoundTeams[] = $byeTeam;
                } else {
                    if(count($nextRoundTeams) % 2 !== 0){
                        $placeholders = collect($matchMap[$round-1] ?? [])->map(fn($m)=>(object)['id'=>$m->id]);
                        $opponent = $placeholders->first(); // lấy placeholder match để đấu với bye
                        for ($leg = 1; $leg <= $numLegs; $leg++) {
                            $match = $type->matches()->create([
                                'tournament_type_id'=>$type->id,
                                'home_team_id'=>$byeTeam->id,
                                'away_team_id'=>null,
                                'round'=>$round,
                                'leg'=>$leg,
                                'is_bye'=>false
                            ]);
                            $roundMatches[] = (object)[
                                'id'=>$match->id,
                                'home'=>$byeTeam,
                                'away'=>$opponent,
                                'winner'=>null,
                                'loser'=>null
                            ];
                        }
                        $nextRoundTeams[] = (object)['id'=>null,'_from_match_id'=>$match->id];
                    } else {
                        $nextRoundTeams[] = $byeTeam;
                    }
                }
            }

            $matchMap[$round] = $roundMatches;
            $currentTeams = $nextRoundTeams;
            $round++;
        }

        // -------------------------------
        // STEP 3: Gán next_match_id & next_position
        // -------------------------------
        $roundKeys = array_keys($matchMap);
        for($i=0;$i<count($roundKeys)-1;$i++){
            $currRound = $matchMap[$roundKeys[$i]];
            $nextRound = $matchMap[$roundKeys[$i+1]]??[];

            foreach($currRound as $index=>$m){
                $nextMatchIndex = floor($index/2);
                $nextPos = ($index%2===0)?'home':'away';
                $nextMatch = $nextRound[$nextMatchIndex]??null;
                if($nextMatch){
                    DB::table('matches')->where('id',$m->id)->update([
                        'next_match_id'=>$nextMatch->id,
                        'next_position'=>$nextPos
                    ]);
                }
            }
        }
    }

    private function generateMixed(TournamentType $type, $teams, $config, $numLegs)
    {
        $teamCount = $teams->count();
        if ($teamCount < 2) return;

        $poolConfig = $config['pool_stage'] ?? [];
        $teamsPerGroup = max(1, (int)($poolConfig['number_competing_teams'] ?? 2));
        $numAdvancing = max(1, (int)($poolConfig['num_advancing_teams'] ?? 1));
        $advancedToNext = (bool)($config['advanced_to_next_round'] ?? false);
        $hasThirdPlace = (bool)($config['has_third_place_match'] ?? false);

        // Tạo vòng bảng
        $chunks = $teams->chunk($teamsPerGroup)->values();
        $advancing = collect();
        $groupMatchMap = [];

        foreach ($chunks as $index => $chunk) {
            $chunk = $chunk->values();
            $count = $chunk->count();

            // Nếu chỉ có 1 đội trong group -> tạo bye match
            if ($count === 1) {
                $byeMatch = $type->matches()->create([
                    'tournament_type_id' => $type->id,
                    'home_team_id' => $chunk[0]->id,
                    'away_team_id' => null,
                    'round' => 1,
                    'leg' => 1,
                    'is_bye' => true,
                    'is_pending' => false,
                ]);
                
                $advancing->push((object)[
                    'team_id' => $chunk[0]->id,
                    '_bye_match' => $byeMatch,
                ]);
                continue;
            }

            // Group bình thường
            $group = $type->groups()->create(['name' => 'Bảng ' . chr(65 + $index)]);
            $groupMatchMap[$group->id] = collect();

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    for ($leg = 1; $leg <= $numLegs; $leg++) {
                        $isReturn = ($leg % 2 === 0);
                        $match = $type->matches()->create([
                            'group_id' => $group->id,
                            'tournament_type_id' => $type->id,
                            'home_team_id' => $isReturn ? $chunk[$j]->id : $chunk[$i]->id,
                            'away_team_id' => $isReturn ? $chunk[$i]->id : $chunk[$j]->id,
                            'round' => 1,
                            'leg' => $leg,
                            'is_bye' => false,
                        ]);
                        $groupMatchMap[$group->id]->push($match);
                    }
                }
            }

            // Placeholder cho đội đi tiếp
            for ($k = 0; $k < min($numAdvancing, $count); $k++) {
                $advancing->push((object)[
                    'team_id' => null,
                    '_from_group' => $group->id,
                    '_rank' => $k + 1,
                ]);
            }
        }

        // Tạo knockout stage với numLegs
        $knockoutRounds = $this->generateKnockoutStage($type, $advancing, $hasThirdPlace, $advancedToNext, $numLegs);

        // Link vòng bảng với knockout
        $this->linkPoolToKnockout($type, $knockoutRounds, $advancing, $groupMatchMap);
    }

    private function generateKnockoutStage(TournamentType $type, $teams, $hasThirdPlace, $advancedToNext = false, $numLegs = 1)
    {
        $teamList = is_array($teams) ? collect($teams) : $teams->values();
        $roundIndex = 2;
        $rounds = collect();

        while ($teamList->count() > 1) {
            $matchesThisRound = collect();
            $nextRoundTeams = collect();
            $teamCount = $teamList->count();

            // Tính số trận và số bye
            $numMatches = intdiv($teamCount, 2);
            $hasBye = ($teamCount % 2 === 1);

            // Tạo các trận đấu bình thường với num_legs
            for ($i = 0; $i < $numMatches; $i++) {
                $homeIdx = $i * 2;
                $awayIdx = $i * 2 + 1;
                
                $home = $teamList->get($homeIdx);
                $away = $teamList->get($awayIdx);

                // Tạo tất cả các legs cho cặp đấu này
                $matchGroup = collect();
                for ($leg = 1; $leg <= $numLegs; $leg++) {
                    $isReturn = ($leg % 2 === 0);
                    
                    $match = $type->matches()->create([
                        'tournament_type_id' => $type->id,
                        'home_team_id' => $isReturn ? $this->getTeamId($away) : $this->getTeamId($home),
                        'away_team_id' => $isReturn ? $this->getTeamId($home) : $this->getTeamId($away),
                        'round' => $roundIndex,
                        'leg' => $leg,
                        'is_pending' => true,
                        'is_bye' => false,
                    ]);

                    $matchGroup->push($match);
                }

                // Chỉ push match đầu tiên vào round (để tracking)
                $matchesThisRound->push($matchGroup->first());
                
                // Winner placeholder (chỉ cần 1 cho cả matchGroup)
                $nextRoundTeams->push((object)[
                    'team_id' => null,
                    '_from_match' => $matchGroup->first()->id,
                ]);
            }

            // Xử lý đội bye (nếu có)
            if ($hasBye) {
                $byeTeam = $teamList->get($teamCount - 1);
                $byeTeamId = $this->getTeamId($byeTeam);

                if ($advancedToNext) {
                    // Tạo trận với best loser từ round trước (có num_legs)
                    $byeMatchGroup = collect();
                    for ($leg = 1; $leg <= $numLegs; $leg++) {
                        $byeMatch = $type->matches()->create([
                            'tournament_type_id' => $type->id,
                            'home_team_id' => $byeTeamId,
                            'away_team_id' => null, // Sẽ được fill sau
                            'round' => $roundIndex,
                            'leg' => $leg,
                            'is_pending' => true,
                            'is_bye' => true,
                            'best_loser_source_round' => $roundIndex - 1,
                        ]);
                        $byeMatchGroup->push($byeMatch);
                    }

                    $matchesThisRound->push($byeMatchGroup->first());
                    
                    // Winner của trận bye
                    $nextRoundTeams->push((object)[
                        'team_id' => null,
                        '_from_match' => $byeMatchGroup->first()->id,
                    ]);
                } else {
                    // Đội bye tự động đi tiếp (chỉ tạo 1 leg vì không đấu)
                    $byeMatch = $type->matches()->create([
                        'tournament_type_id' => $type->id,
                        'home_team_id' => $byeTeamId,
                        'away_team_id' => null,
                        'round' => $roundIndex,
                        'leg' => 1,
                        'is_pending' => false,
                        'is_bye' => true,
                    ]);

                    $matchesThisRound->push($byeMatch);
                    
                    // Đội này đi tiếp luôn
                    $nextRoundTeams->push((object)[
                        'team_id' => $byeTeamId,
                        '_bye_match' => $byeMatch,
                    ]);
                }
            }

            $rounds->put($roundIndex, $matchesThisRound);
            $teamList = $nextRoundTeams;
            $roundIndex++;
        }

        if ($rounds->isEmpty()) return collect();

        // Link next_match_id (chỉ link cho leg 1 của mỗi cặp)
        $finalRound = $roundIndex - 1;
        for ($r = 2; $r < $finalRound; $r++) {
            $currMatches = $rounds->get($r, collect());
            $nextMatches = $rounds->get($r + 1, collect());
            
            foreach ($currMatches as $idx => $match) {
                if ($match->is_bye && !$match->away_team_id) continue;
                
                $targetIdx = intdiv($idx, 2);
                $target = $nextMatches->get($targetIdx);
                if (!$target) continue;
                
                $position = ($idx % 2 === 0) ? 'home' : 'away';
                
                // Link tất cả legs của cùng 1 cặp đấu
                $allLegs = $type->matches()
                    ->where('round', $match->round)
                    ->where('home_team_id', $match->home_team_id)
                    ->where('away_team_id', $match->away_team_id)
                    ->get();
                
                foreach ($allLegs as $legMatch) {
                    $legMatch->update([
                        'next_match_id' => $target->id,
                        'next_position' => $position,
                    ]);
                }
            }
        }

        // Trận tranh hạng 3 (có num_legs)
        if ($hasThirdPlace) {
            $semiRound = $finalRound - 1;
            $semis = $rounds->get($semiRound, collect());
            
            if ($semis->count() >= 2) {
                $firstSemi = $semis->get(0);
                $secondSemi = $semis->get(1);
                
                // Tạo tất cả legs cho trận tranh hạng 3
                $thirdPlaceMatches = collect();
                for ($leg = 1; $leg <= $numLegs; $leg++) {
                    $third = $type->matches()->create([
                        'tournament_type_id' => $type->id,
                        'round' => $finalRound + 1,
                        'leg' => $leg,
                        'is_third_place' => true,
                        'is_pending' => true,
                    ]);
                    $thirdPlaceMatches->push($third);
                }
                
                // Link loser từ semis vào trận tranh hạng 3 (leg 1)
                if ($firstSemi) {
                    // Link tất cả legs của semi 1
                    $allLegs1 = $type->matches()
                        ->where('round', $firstSemi->round)
                        ->where('home_team_id', $firstSemi->home_team_id)
                        ->where('away_team_id', $firstSemi->away_team_id)
                        ->get();
                    
                    foreach ($allLegs1 as $legMatch) {
                        $legMatch->update([
                            'loser_next_match_id' => $thirdPlaceMatches->first()->id,
                            'loser_next_position' => 'home',
                        ]);
                    }
                }
                
                if ($secondSemi) {
                    // Link tất cả legs của semi 2
                    $allLegs2 = $type->matches()
                        ->where('round', $secondSemi->round)
                        ->where('home_team_id', $secondSemi->home_team_id)
                        ->where('away_team_id', $secondSemi->away_team_id)
                        ->get();
                    
                    foreach ($allLegs2 as $legMatch) {
                        $legMatch->update([
                            'loser_next_match_id' => $thirdPlaceMatches->first()->id,
                            'loser_next_position' => 'away',
                        ]);
                    }
                }
            }
        }

        return $rounds;
    }

    private function getTeamId($placeholder)
    {
        if (!$placeholder) return null;
        if (is_object($placeholder) && isset($placeholder->team_id)) {
            return $placeholder->team_id;
        }
        return null;
    }

    private function linkPoolToKnockout(TournamentType $type, $knockoutRounds, $advancing, $groupMatchMap)
    {
        if (!($knockoutRounds instanceof Collection)) {
            $knockoutRounds = collect($knockoutRounds);
        }
        
        $firstRoundMatches = $knockoutRounds->get(2, collect());
        if ($firstRoundMatches->isEmpty()) return;

        $knockoutIndex = 0;
        $slotCount = $firstRoundMatches->count() * 2;

        foreach ($advancing as $placeholder) {
            if ($knockoutIndex >= $slotCount) break;

            $matchIndex = intdiv($knockoutIndex, 2);
            $position = ($knockoutIndex % 2 === 0) ? 'home' : 'away';
            $knockoutMatch = $firstRoundMatches->get($matchIndex);

            if (!$knockoutMatch) {
                $knockoutIndex++;
                continue;
            }

            if (isset($placeholder->_from_group)) {
                $groupId = $placeholder->_from_group;
                $matches = $groupMatchMap[$groupId] ?? collect();
                
                if (!($matches instanceof Collection)) {
                    $matches = collect($matches);
                }
                
                foreach ($matches as $gm) {
                    $gm->update([
                        'next_match_id' => $knockoutMatch->id,
                        'next_position' => $position,
                    ]);
                }
            } elseif (isset($placeholder->_bye_match)) {
                $byeRef = $placeholder->_bye_match;
                if (is_object($byeRef) && method_exists($byeRef, 'update')) {
                    $byeRef->update([
                        'next_match_id' => $knockoutMatch->id,
                        'next_position' => $position,
                    ]);
                }
            }

            $knockoutIndex++;
        }
    }
     /**
     * Lấy toàn bộ bracket cho tournament type
     * Trả về cấu trúc phân theo round để hiển thị bracket chart
     */
    public function getBracket(TournamentType $tournamentType)
    {
        try {
            $format = $tournamentType->format;
            
            switch ($format) {
                case TournamentType::FORMAT_ROUND_ROBIN:
                    return $this->getRoundRobinSchedule($tournamentType);
                    
                case TournamentType::FORMAT_ELIMINATION:
                    return $this->getEliminationBracket($tournamentType);
                    
                case TournamentType::FORMAT_MIXED:
                    return $this->getMixedBracket($tournamentType);
                    
                default:
                    return ResponseHelper::error('Format không hợp lệ', 400);
            }
        } catch (\Throwable $e) {
            return ResponseHelper::error('Lỗi khi lấy bracket: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Round Robin - trả về danh sách trận theo thứ tự
     */
    private function getRoundRobinSchedule(TournamentType $type)
    {
        $matches = $type->matches()
            ->with(['homeTeam.members', 'awayTeam.members'])
            ->orderBy('leg')
            ->get()
            ->map(function ($match) {
                return [
                    'id' => $match->id,
                    'leg' => $match->leg,
                    'home_team' => $this->formatTeam($match->homeTeam),
                    'away_team' => $this->formatTeam($match->awayTeam),
                    'home_score' => $match->home_score,
                    'away_score' => $match->away_score,
                    'status' => $match->status,
                    'scheduled_at' => $match->scheduled_at,
                    'is_completed' => $match->status === 'completed',
                ];
            });

        return ResponseHelper::success([
            'format' => 'round_robin',
            'matches' => $matches,
            'total_matches' => $matches->count(),
        ]);
    }

    /**
     * Elimination - trả về bracket theo round
     */
    private function getEliminationBracket(TournamentType $type)
    {
        $matches = $type->matches()
            ->with(['homeTeam.members', 'awayTeam.members'])
            ->orderBy('round')
            ->orderBy('leg')
            ->get();

        $bracket = $matches->groupBy('round')->map(function ($roundMatches, $round) {
            $grouped = $roundMatches->groupBy(function ($match) {
                return $match->home_team_id . '_' . $match->away_team_id;
            })->values();

            return [
                'round' => $round,
                'round_name' => $this->getRoundName($round, $roundMatches->count()),
                'matches' => $grouped->map(function ($matchGroup) {
                    $firstMatch = $matchGroup->first();
                    
                    return [
                        'match_id' => $firstMatch->id,
                        'home_team' => $this->formatTeam($firstMatch->homeTeam),
                        'away_team' => $this->formatTeam($firstMatch->awayTeam),
                        'is_bye' => $firstMatch->is_bye,
                        'is_third_place' => $firstMatch->is_third_place ?? false,
                        'legs' => $matchGroup->map(function ($leg) {
                            return [
                                'id' => $leg->id,
                                'leg' => $leg->leg,
                                'home_score' => $leg->home_score,
                                'away_score' => $leg->away_score,
                                'status' => $leg->status,
                                'scheduled_at' => $leg->scheduled_at,
                                'is_completed' => $leg->status === 'completed',
                            ];
                        })->values(),
                        'aggregate_score' => $this->calculateAggregateScore($matchGroup),
                        'winner_team_id' => $this->determineWinner($matchGroup),
                        'next_match_id' => $firstMatch->next_match_id,
                        'next_position' => $firstMatch->next_position,
                    ];
                })->values(),
            ];
        })->values();

        return ResponseHelper::success([
            'format' => 'elimination',
            'bracket' => $bracket,
            'total_rounds' => $bracket->count(),
        ]);
    }

    /**
     * Mixed - trả về vòng bảng + knockout
     */
    private function getMixedBracket(TournamentType $type)
    {
        // Vòng bảng (round = 1)
        $poolMatches = $type->matches()
            ->with(['homeTeam.members', 'awayTeam.members', 'group'])
            ->where('round', 1)
            ->orderBy('group_id')
            ->orderBy('leg')
            ->get();

        $poolStage = $poolMatches->groupBy('group_id')->map(function ($groupMatches, $groupId) {
            $group = $groupMatches->first()->group;
            
            return [
                'group_id' => $groupId,
                'group_name' => $group ? $group->name : 'Bye',
                'matches' => $groupMatches->groupBy(function ($match) {
                    return $match->home_team_id . '_' . $match->away_team_id;
                })->values()->map(function ($matchGroup) {
                    $firstMatch = $matchGroup->first();
                    
                    return [
                        'match_id' => $firstMatch->id,
                        'home_team' => $this->formatTeam($firstMatch->homeTeam),
                        'away_team' => $this->formatTeam($firstMatch->awayTeam),
                        'is_bye' => $firstMatch->is_bye,
                        'legs' => $matchGroup->map(function ($leg) {
                            return [
                                'id' => $leg->id,
                                'leg' => $leg->leg,
                                'home_score' => $leg->home_score,
                                'away_score' => $leg->away_score,
                                'status' => $leg->status,
                                'scheduled_at' => $leg->scheduled_at,
                            ];
                        })->values(),
                    ];
                })->values(),
                'standings' => $this->calculateGroupStandings($groupMatches),
            ];
        })->values();

        // Vòng knockout (round >= 2)
        $knockoutMatches = $type->matches()
            ->with(['homeTeam.members', 'awayTeam.members'])
            ->where('round', '>=', 2)
            ->orderBy('round')
            ->orderBy('leg')
            ->get();

        $knockoutStage = $knockoutMatches->groupBy('round')->map(function ($roundMatches, $round) {
            $grouped = $roundMatches->groupBy(function ($match) {
                return $match->home_team_id . '_' . $match->away_team_id;
            })->values();

            return [
                'round' => $round,
                'round_name' => $this->getRoundName($round, $grouped->count()),
                'matches' => $grouped->map(function ($matchGroup) {
                    $firstMatch = $matchGroup->first();
                    
                    return [
                        'match_id' => $firstMatch->id,
                        'home_team' => $this->formatTeam($firstMatch->homeTeam),
                        'away_team' => $this->formatTeam($firstMatch->awayTeam),
                        'is_bye' => $firstMatch->is_bye,
                        'is_third_place' => $firstMatch->is_third_place ?? false,
                        'best_loser_source_round' => $firstMatch->best_loser_source_round ?? null,
                        'legs' => $matchGroup->map(function ($leg) {
                            return [
                                'id' => $leg->id,
                                'leg' => $leg->leg,
                                'home_score' => $leg->home_score,
                                'away_score' => $leg->away_score,
                                'status' => $leg->status,
                                'scheduled_at' => $leg->scheduled_at,
                            ];
                        })->values(),
                        'aggregate_score' => $this->calculateAggregateScore($matchGroup),
                        'winner_team_id' => $this->determineWinner($matchGroup),
                        'next_match_id' => $firstMatch->next_match_id,
                        'next_position' => $firstMatch->next_position,
                    ];
                })->values(),
            ];
        })->values();

        return ResponseHelper::success([
            'format' => 'mixed',
            'pool_stage' => $poolStage,
            'knockout_stage' => $knockoutStage,
        ]);
    }

    /**
     * Format team data
     */
    private function formatTeam($team)
    {
        if (!$team) {
            return [
                'id' => null,
                'name' => 'TBD',
                'logo' => null,
                'members' => [],
            ];
        }

        return [
            'id' => $team->id,
            'name' => $team->name,
            'logo' => $team->logo,
            'members' => $team->members->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar ?? null,
                ];
            }),
        ];
    }

    /**
     * Tính tổng điểm aggregate (cho 2 legs)
     */
    private function calculateAggregateScore($matchGroup)
    {
        $homeTotal = 0;
        $awayTotal = 0;

        foreach ($matchGroup as $match) {
            if ($match->status === 'completed') {
                $homeTotal += $match->home_score ?? 0;
                $awayTotal += $match->away_score ?? 0;
            }
        }

        return [
            'home' => $homeTotal,
            'away' => $awayTotal,
        ];
    }

    /**
     * Xác định winner (sau khi tất cả legs hoàn thành)
     */
    private function determineWinner($matchGroup)
    {
        $allCompleted = $matchGroup->every(fn($m) => $m->status === 'completed');
        
        if (!$allCompleted) {
            return null;
        }

        $aggregate = $this->calculateAggregateScore($matchGroup);
        
        if ($aggregate['home'] > $aggregate['away']) {
            return $matchGroup->first()->home_team_id;
        } elseif ($aggregate['away'] > $aggregate['home']) {
            return $matchGroup->first()->away_team_id;
        }

        return null; // Draw - cần penalty hoặc away goals rule
    }

    /**
     * Tính bảng xếp hạng cho group
     */
    private function calculateGroupStandings($groupMatches)
    {
        $standings = [];
        
        foreach ($groupMatches as $match) {
            if ($match->status !== 'completed') continue;

            $homeId = $match->home_team_id;
            $awayId = $match->away_team_id;

            if (!isset($standings[$homeId])) {
                $standings[$homeId] = [
                    'team' => $this->formatTeam($match->homeTeam),
                    'played' => 0,
                    'won' => 0,
                    'draw' => 0,
                    'lost' => 0,
                    'goals_for' => 0,
                    'goals_against' => 0,
                    'goal_difference' => 0,
                    'points' => 0,
                ];
            }

            if (!isset($standings[$awayId])) {
                $standings[$awayId] = [
                    'team' => $this->formatTeam($match->awayTeam),
                    'played' => 0,
                    'won' => 0,
                    'draw' => 0,
                    'lost' => 0,
                    'goals_for' => 0,
                    'goals_against' => 0,
                    'goal_difference' => 0,
                    'points' => 0,
                ];
            }

            $standings[$homeId]['played']++;
            $standings[$awayId]['played']++;
            $standings[$homeId]['goals_for'] += $match->home_score ?? 0;
            $standings[$homeId]['goals_against'] += $match->away_score ?? 0;
            $standings[$awayId]['goals_for'] += $match->away_score ?? 0;
            $standings[$awayId]['goals_against'] += $match->home_score ?? 0;

            if ($match->home_score > $match->away_score) {
                $standings[$homeId]['won']++;
                $standings[$homeId]['points'] += 3;
                $standings[$awayId]['lost']++;
            } elseif ($match->home_score < $match->away_score) {
                $standings[$awayId]['won']++;
                $standings[$awayId]['points'] += 3;
                $standings[$homeId]['lost']++;
            } else {
                $standings[$homeId]['draw']++;
                $standings[$awayId]['draw']++;
                $standings[$homeId]['points']++;
                $standings[$awayId]['points']++;
            }
        }

        // Tính goal difference và sort
        $standings = collect($standings)->map(function ($team) {
            $team['goal_difference'] = $team['goals_for'] - $team['goals_against'];
            return $team;
        })->sortByDesc('points')
          ->sortByDesc('goal_difference')
          ->sortByDesc('goals_for')
          ->values();

        return $standings;
    }

    /**
     * Lấy tên round
     */
    private function getRoundName($round, $matchCount)
    {
        if ($round === 1) return 'Vòng bảng';
        
        // Dựa vào số trận để xác định tên
        if ($matchCount === 1) return 'Chung kết';
        if ($matchCount === 2) return 'Bán kết';
        if ($matchCount === 4) return 'Tứ kết';
        if ($matchCount === 8) return 'Vòng 1/8';
        if ($matchCount === 16) return 'Vòng 1/16';
        
        return "Round {$round}";
    }

    public function getRank($tournament_id)
    {
        $type = TournamentType::where('tournament_id', $tournament_id)->first();
        if (!$type) {
            return ResponseHelper::error('Tournament type not found', 404);
        }
    
        $teams = $type->tournament->teams()->with('members')->get();
    
        $rankings = $teams->map(function ($team) use ($type) {
            $matches = $type->matches()
                ->where(function ($query) use ($team) {
                    $query->where('home_team_id', $team->id)
                        ->orWhere('away_team_id', $team->id);
                })
                ->where('status', 'completed')
                ->get();
    
            $played = $matches->count();
            $wins = $matches->where('winner_id', $team->id)->count();
            $losses = $played - $wins;
    
            // Điểm 3 cho thắng, 0 cho thua
            $points = $wins * 3;
    
            // Tính win_rate
            $winRate = $played > 0 ? round(($wins / $played) * 100, 2) : 0;
    
            return [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'played' => $played,
                'wins' => $wins,
                'losses' => $losses,
                'points' => $points,
                'win_rate' => $winRate,
            ];
        });
    
        // Sắp xếp theo thứ tự: points > win_rate > wins > losses
        $sortedRankings = $rankings
            ->sortByDesc('points')
            ->sortByDesc('win_rate')
            ->values();
    
        return ResponseHelper::success([
            'rankings' => $sortedRankings,
        ]);
    }
}