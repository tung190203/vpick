<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Http\Resources\TournamentTypeResource;
use App\Models\Group;
use App\Models\Matches;
use App\Models\PoolAdvancementRule;
use App\Models\TeamRanking;
use App\Models\Tournament;
use App\Models\TournamentType;
use App\Services\TournamentService;
use Illuminate\Http\Request;
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

        $tournament = Tournament::withFullRelations()->find($validated['tournament_id']);
        if($tournament->teams()->count() < 2) {
            return ResponseHelper::error('Cần có ít nhất 2 đội tham gia để tạo thể thức', 422);
        }

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
    
            // ✅ TẠO BẢNG TRỐNG CHO FORMAT MIXED
            if ($type->format === TournamentType::FORMAT_MIXED) {
                $this->createEmptyGroups($type);
            } else {
                $this->autoGenerateMatches($type);
            }
    
            DB::commit();
            return ResponseHelper::success(new TournamentTypeResource($type), 'Tạo thể thức thành công');
        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseHelper::error('Lỗi khi tạo thể thức: ' . $e->getMessage(), 500);
        }
    }

    public function autoGenerateMatches(TournamentType $tournamentType)
    {
        $completedMatches = $tournamentType->matches()
            ->where('status', 'completed')
            ->exists();

        if ($completedMatches) {
            return ResponseHelper::error(
                'Không thể sắp xếp lại. Đã có trận đấu hoàn thành.',
                400
            );
        }

        DB::beginTransaction();
        try {
            // Xóa assignment và matches cũ (nếu có)
            foreach ($tournamentType->groups as $group) {
                $group->teams()->detach();
            }
            $tournamentType->matches()->each(function ($match) {
                $match->results()->delete();
                $match->delete();
            });
            if ($tournamentType->format == TournamentType::FORMAT_MIXED) {
                $tournamentType->advancementRules()->delete();
            }

            // ✅ GỌI HÀM GENERATE CŨ (TỰ ĐỘNG CHIA ĐỘI)
            $this->generateMatchesForType($tournamentType);

            DB::commit();
            return ResponseHelper::success(
                new TournamentTypeResource($tournamentType->fresh()), 
                'Tự động tạo lịch thi đấu thành công'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseHelper::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * ✅ TẠO CÁC BẢNG TRỐNG DỰA VÀO CONFIG
     */
    protected function createEmptyGroups(TournamentType $type)
    {
        $config = $type->format_specific_config ?? [];
        $mainConfig = is_array($config) && isset($config[0]) ? $config[0] : [];
        $poolConfig = $mainConfig['pool_stage'] ?? [];
        $numGroups = max(1, (int)($poolConfig['number_competing_teams'] ?? 2));
    
        // Xóa groups cũ nếu có
        $type->groups()->delete();
    
        // Tạo groups mới
        for ($i = 0; $i < $numGroups; $i++) {
            $type->groups()->create([
                'name' => 'Bảng ' . chr(65 + $i) // A, B, C, D...
            ]);
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
            // ✅ KIỂM TRA SỐ BẢNG CÓ THAY ĐỔI KHÔNG
            $oldNumGroups = $tournamentType->groups()->count();
            $newNumGroups = null;
            
            if (!empty($validated['format_specific_config'])) {
                $mainConfig = is_array($validated['format_specific_config']) && isset($validated['format_specific_config'][0]) 
                    ? $validated['format_specific_config'][0] 
                    : $validated['format_specific_config'];
                $poolConfig = $mainConfig['pool_stage'] ?? [];
                $newNumGroups = max(1, (int)($poolConfig['number_competing_teams'] ?? 2));
            }
    
            // Xoá toàn bộ cấu hình cũ trước khi cập nhật
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
    
            // ✅ NẾU SỐ BẢNG THAY ĐỔI -> TẠO LẠI BẢNG TRỐNG
            if ($tournamentType->format === TournamentType::FORMAT_MIXED && 
                $newNumGroups !== null && 
                $newNumGroups !== $oldNumGroups) {
                
                // Xóa assignment và matches cũ
                foreach ($tournamentType->groups as $group) {
                    $group->teams()->detach();
                }
                $tournamentType->matches()->each(function ($match) {
                    $match->results()->delete();
                    $match->delete();
                });
                
                // Tạo lại groups
                $this->createEmptyGroups($tournamentType);
            } else {
                // ✅ NẾU KHÔNG THAY ĐỔI SỐ BẢNG -> CHỈ REGENERATE MATCHES
                $this->generateMatchesForType($tournamentType);
            }
    
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
        $completedMatches = $tournamentType->matches()
        ->where('status', 'completed')
        ->exists();

        if ($completedMatches) {
            return ResponseHelper::error(
                'Không thể chia lại cặp đấu. Đã có trận đấu hoàn thành thuộc thể thức này.', 
                400
            );
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
        $scheduleTeams = collect($teams)->pluck('id')->toArray();
        $isOdd = $teamCount % 2 !== 0;
        if ($isOdd) {
            $scheduleTeams[] = 'BYE';
            $teamCount++;
        }
        $totalRounds = $teamCount - 1; 
        $matches = [];
        $matchNumber = 0;

        for ($leg = 1; $leg <= $numLegs; $leg++) {
            for ($round = 1; $round <= $totalRounds; $round++) {
                $halfSize = $teamCount / 2;
                $homeTeams = array_slice($scheduleTeams, 0, $halfSize);
                $awayTeams = array_slice($scheduleTeams, $halfSize);
                $awayTeams = array_reverse($awayTeams);

                for ($i = 0; $i < $halfSize; $i++) {
                    $homeId = $homeTeams[$i];
                    $awayId = $awayTeams[$i];

                    if ($homeId === 'BYE' || $awayId === 'BYE') {
                        continue; 
                    }
                    
                    $matchNumber++;
                    
                    $isReturnLeg = ($leg % 2 === 0);
                    $finalHomeId = ($isReturnLeg) ? $awayId : $homeId;
                    $finalAwayId = ($isReturnLeg) ? $homeId : $awayId;

                    $matches[] = [
                        'name_of_match' => "Trận đấu số {$matchNumber}",
                        'home_team_id' => $finalHomeId,
                        'away_team_id' => $finalAwayId,
                        'tournament_type_id' => $type->id,
                        'leg' => $leg,
                        'round' => $round,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                $firstTeam = array_shift($scheduleTeams);
                $lastTeam = array_pop($scheduleTeams);
                array_unshift($scheduleTeams, $firstTeam, $lastTeam);
            }
        }

        // Insert tất cả một lần
        if (!empty($matches)) {
            Matches::insert($matches);
        }
    }

    private function generateElimination(TournamentType $type, $teams, $config, $numLegs)
    {
        $teamCount = count($teams);
        if ($teamCount < 2) return;

        // ✅ FIX: Xử lý config dạng array hoặc object
        $mainConfig = is_array($config) && isset($config[0]) ? $config[0] : $config;

        $seedingRules = $mainConfig['seeding_rules'] ?? [];
        $byeSelectionOrder = filter_var($mainConfig['advanced_to_next_round'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $hasThirdPlace = filter_var($mainConfig['has_third_place_match'] ?? false, FILTER_VALIDATE_BOOLEAN); // ✅ FIX

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
                    if (!$sportId || empty($allUserIds)) {
                        $teams = $teams->shuffle()->values();
                        break;
                    }

                    $userScores = DB::table('user_sport as us')
                        ->join('user_sport_scores as uss', 'us.id', '=', 'uss.user_sport_id')
                        ->where('us.sport_id', $sportId)
                        ->where('uss.score_type', 'vndupr_score')
                        ->whereIn('us.user_id', $allUserIds)
                        ->pluck('uss.score_value', 'us.user_id')
                        ->map(fn($v) => (float)$v)
                        ->toArray();

                    $teams = $teams->map(function ($team) use ($userScores) {
                        $userIds = collect($team->members)->pluck('user_id')->filter();
                        $scores = $userIds->map(fn($uid) => $userScores[$uid] ?? 0)->toArray();
                        $team->_seed_meta = ['level' => count($scores) ? array_sum($scores) / count($scores) : 0];
                        return $team;
                    })->sortByDesc(fn($t) => $t->_seed_meta['level'])->values();
                    break;

                case TournamentType::SEED_SAME_CLUB_AVOID:
                    $byClub = $teams->groupBy(fn($t) => $t->club_id ?? 'no_club');
                    $interleaved = collect();
                    while ($byClub->isNotEmpty()) {
                        foreach ($byClub as $club => $arr) {
                            if ($arr->isNotEmpty()) $interleaved->push($arr->shift());
                            if ($arr->isEmpty()) $byClub->forget($club);
                            else $byClub->put($club, $arr);
                        }
                    }
                    $teams = $interleaved->values();
                    break;

                default:
                    $teams = $teams->shuffle()->values();
                    break;
            }
        }

        // -------------------------------
        // STEP 2: Generate bracket (với numLegs)
        // -------------------------------
        $round = 1;
        $currentTeams = $teams->values()->all();
        $matchMap = [];

        while (count($currentTeams) > 1) {
            $nextRoundTeams = [];
            $roundPairs = [];
            $numTeams = count($currentTeams);
            $hasOdd = $numTeams % 2 !== 0;

            $byeTeam = $hasOdd ? array_pop($currentTeams) : null;

            $matchNumber = 0;
            for ($i = 0; $i < count($currentTeams); $i += 2) {
                $home = $currentTeams[$i];
                $away = $currentTeams[$i + 1] ?? null;
                $matchNumber++;

                $pairMatchIds = [];
                for ($leg = 1; $leg <= $numLegs; $leg++) {
                    $isReturn = ($leg % 2 === 0);
                    $match = $type->matches()->create([
                        'tournament_type_id' => $type->id,
                        'name_of_match' => "Trận đấu số {$matchNumber}",
                        'home_team_id' => $isReturn ? ($away->id ?? null) : ($home->id ?? null),
                        'away_team_id' => $isReturn ? ($home->id ?? null) : ($away->id ?? null),
                        'round' => $round,
                        'leg' => $leg,
                        'is_bye' => false
                    ]);
                    $pairMatchIds[] = $match->id;
                }

                $roundPairs[] = (object)[
                    'match_ids' => $pairMatchIds,
                    'home' => $home,
                    'away' => $away
                ];
                $nextRoundTeams[] = (object)['id' => null, '_from_pair_index' => count($roundPairs) - 1];
            }

            // xử lý đội bye
            if($byeTeam){
                $pairMatchIds = [];
                for ($leg = 1; $leg <= $numLegs; $leg++) {
                    $match = $type->matches()->create([
                        'tournament_type_id' => $type->id,
                        'name_of_match' => "Trận đấu số " . ($matchNumber + 1),
                        'home_team_id' => $byeTeam->id,
                        'away_team_id' => null,
                        'round' => $round,
                        'leg' => $leg,
                        'is_bye' => true
                    ]);
                    $pairMatchIds[] = $match->id;
                }
                $roundPairs[] = (object)['match_ids' => $pairMatchIds, 'home' => $byeTeam, 'away' => null];
                $nextRoundTeams[] = $byeTeam;
            }

            $matchMap[$round] = $roundPairs;
            $currentTeams = $nextRoundTeams;
            $round++;
        }

        // -------------------------------
        // STEP 3: Gán next_match_id & next_position
        // -------------------------------
        $roundKeys = array_keys($matchMap);
        for ($i = 0; $i < count($roundKeys) - 1; $i++) {
            $currRoundPairs = $matchMap[$roundKeys[$i]];
            $nextRoundPairs = $matchMap[$roundKeys[$i + 1]] ?? [];

            foreach ($currRoundPairs as $pairIndex => $pair) {
                $nextPairIndex = floor($pairIndex / 2);
                $nextPos = ($pairIndex % 2 === 0) ? 'home' : 'away';
                
                $nextPair = $nextRoundPairs[$nextPairIndex] ?? null;
                if ($nextPair) {
                    $targetMatchId = $nextPair->match_ids[0];

                    foreach ($pair->match_ids as $mId) {
                        DB::table('matches')->where('id', $mId)->update([
                            'next_match_id' => $targetMatchId,
                            'next_position' => $nextPos
                        ]);
                    }
                }
            }
        }

        // ✅ STEP 4: TẠO TRẬN TRANH HẠNG 3 (NẾU CÓ)
        if ($hasThirdPlace && count($roundKeys) >= 2) {
            $finalRound = max($roundKeys);
            $semiRound = $finalRound - 1;
            $semiPairs = $matchMap[$semiRound] ?? [];

            if (count($semiPairs) >= 2) {
                $firstSemiPair = $semiPairs[0];
                $secondSemiPair = $semiPairs[1];

                $firstSemiId = $firstSemiPair->match_ids[0];
                $secondSemiId = $secondSemiPair->match_ids[0];

                $firstThirdPlaceId = null;

                // Tạo trận tranh hạng 3 cho từng leg
                for ($leg = 1; $leg <= $numLegs; $leg++) {
                    $third = $type->matches()->create([
                        'tournament_type_id' => $type->id,
                        'round' => $finalRound + 1,
                        'leg' => $leg,
                        'is_third_place' => true,
                        'status' => 'pending',
                        'name_of_match' => "Tranh hạng Ba",
                    ]);

                    if ($leg === 1) {
                        $firstThirdPlaceId = $third->id;
                    }
                }

                // Link semi-final losers vào trận tranh hạng 3
                DB::table('matches')
                    ->where('id', $firstSemiId)
                    ->update([
                        'loser_next_match_id' => $firstThirdPlaceId,
                        'loser_next_position' => 'home',
                    ]);
                DB::table('matches')
                    ->where('id', $secondSemiId)
                    ->update([
                        'loser_next_match_id' => $firstThirdPlaceId,
                        'loser_next_position' => 'away',
                    ]);
            }
        }
    }
    private function generateMixed(TournamentType $type, $teams, $config, $numLegs)
    {
        $matchNumber = 0;

        $mainConfig = is_array($config) && isset($config[0]) ? $config[0] : [];        
        $poolConfig = $mainConfig['pool_stage'] ?? [];
        $numAdvancing = max(1, (int)($poolConfig['num_advancing_teams'] ?? 1));
        $advancedToNext = filter_var($mainConfig['advanced_to_next_round'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $hasThirdPlace = filter_var($mainConfig['has_third_place_match'] ?? false, FILTER_VALIDATE_BOOLEAN);
    
        // ✅ KIỂM TRA: Có groups với teams assigned không?
        $groups = $type->groups()->with('teams.members')->get();
        $hasAssignedTeams = $groups->isNotEmpty() && $groups->some(fn($g) => $g->teams->isNotEmpty());
    
        if ($hasAssignedTeams) {
            // ✅ TRƯỜNG HỢP 1: ĐÃ ASSIGN TEAMS VÀO BẢNG
            $chunks = $groups->map(fn($g) => $g->teams)->filter(fn($chunk) => $chunk->count() > 0)->values();
        } else {
            // ✅ TRƯỜNG HỢP 2: AUTO GENERATE - CHIA ĐỘI TỰ ĐỘNG (LOGIC CŨ)
            $teamCount = $teams->count();
            if ($teamCount < 2) return;
    
            $numGroups = max(1, (int)($poolConfig['number_competing_teams'] ?? 2)); 
            $baseTeamsPerGroup = floor($teamCount / $numGroups);
            $remainder = $teamCount % $numGroups;
            
            $chunks = collect();
            $offset = 0;
            for ($i = 0; $i < $numGroups; $i++) {
                $groupSize = $baseTeamsPerGroup + ($i < $remainder ? 1 : 0);
                if ($groupSize > 0) {
                    $groupTeams = $teams->slice($offset, $groupSize)->values();
                    $chunks->push($groupTeams);               
                    // ✅ LƯU TEAMS VÀO GROUP_TEAM
                    $group = $groups->get($i);
                    if ($group) {
                        $syncData = [];
                        foreach ($groupTeams as $order => $team) {
                            $syncData[$team->id] = ['order' => $order];
                        }
                        $group->teams()->sync($syncData);
                    }
                    
                    $offset += $groupSize;
                }
            }
        
            $chunks = $chunks->filter(fn($chunk) => $chunk->count() > 0)->values();
        }
        $advancingByRank = collect();
        $groupObjects = collect();
    
        // ===== PHASE 2: TẠO VÒNG BẢNG (ROUND ROBIN) =====
        foreach ($chunks as $index => $chunk) {
            $chunk = $chunk->values();
            $count = $chunk->count();
    
            // Nếu chỉ có 1 đội trong group -> tạo bye match
            if ($count === 1) {
                $matchNumber++;
                    $group = $type->groups()->create(['name' => 'Bảng ' . chr(65 + $index)]);
    
                $byeMatch = $type->matches()->create([
                    'tournament_type_id' => $type->id,
                    'home_team_id' => $chunk[0]->id,
                    'away_team_id' => null,
                    'round' => 1,
                    'leg' => 1,
                    'is_bye' => true,
                    'status' => 'pending',
                    'name_of_match' => "Trận đấu số {$matchNumber}",
                ]);
                
                if (!isset($advancingByRank[0])) {
                    $advancingByRank[0] = collect();
                }
                $advancingByRank[0]->push((object)[
                    'team_id' => $chunk[0]->id,
                    '_bye_match' => $byeMatch,
                    '_group_id' => null,
                    '_group_index' => $index,
                    '_rank' => 1,
                ]);
                continue;
            }
    
            // ✅ Group bình thường (2+ đội)
            $group = $groups->get($index);
            if (!$group) {
                continue;
            }
            $groupObjects->push($group);
    
            // Thuật toán Round Robin (Circle Method)
            $scheduleTeams = $chunk->pluck('id')->toArray();
            $isOdd = $count % 2 !== 0;
            if ($isOdd) {
                $scheduleTeams[] = 'BYE';
                $count++;
            }
            $totalRounds = $count - 1;
    
            // Tạo matches cho từng leg
            for ($leg = 1; $leg <= $numLegs; $leg++) {
                $currentSchedule = $scheduleTeams; // Reset schedule mỗi leg
    
                for ($round = 1; $round <= $totalRounds; $round++) {
                    $halfSize = $count / 2;
                    $homeTeams = array_slice($currentSchedule, 0, $halfSize);
                    $awayTeams = array_reverse(array_slice($currentSchedule, $halfSize));
    
                    for ($i = 0; $i < $halfSize; $i++) {
                        $homeId = $homeTeams[$i];
                        $awayId = $awayTeams[$i];
    
                        if ($homeId === 'BYE' || $awayId === 'BYE') {
                            continue;
                        }
    
                        $matchNumber++;
    
                        // Đảo sân cho lượt về
                        $isReturnLeg = ($leg % 2 === 0);
                        $finalHomeId = $isReturnLeg ? $awayId : $homeId;
                        $finalAwayId = $isReturnLeg ? $homeId : $awayId;
    
                        $type->matches()->create([
                            'group_id' => $group->id,
                            'tournament_type_id' => $type->id,
                            'home_team_id' => $finalHomeId,
                            'away_team_id' => $finalAwayId,
                            'round' => 1, // Pool stage luôn là round 1
                            'leg' => $leg,
                            'is_bye' => false,
                            'status' => 'pending',
                            'name_of_match' => "Trận đấu số {$matchNumber}",
                        ]);
                    }
    
                    // Rotate schedule (Circle Method)
                    $firstTeam = array_shift($currentSchedule);
                    $lastTeam = array_pop($currentSchedule);
                    array_unshift($currentSchedule, $firstTeam, $lastTeam);
                }
            }
    
            // Thu thập placeholder theo hạng cho knockout
            for ($k = 0; $k < min($numAdvancing, $chunk->count()); $k++) {
                if (!isset($advancingByRank[$k])) {
                    $advancingByRank[$k] = collect();
                }
                
                $advancingByRank[$k]->push((object)[
                    'team_id' => null,
                    '_from_group' => $group->id,
                    '_group_index' => $index,
                    '_rank' => $k + 1,
                ]);
            }
        }
    
        // ✅ Cross-matching pattern: Nhất A vs Nhì B, Nhất B vs Nhì A
        $advancing = collect();
        
        // Lấy tất cả nhất bảng (rank 1)
        $firstPlaceTeams = $advancingByRank->get(0, collect());
        // Lấy tất cả nhì bảng (rank 2)
        $secondPlaceTeams = $advancingByRank->get(1, collect());
        
        // Xếp theo pattern: Nhất A, Nhì B, Nhất B, Nhì A, Nhất C, Nhì D, Nhất D, Nhì C...
        $numFirstPlace = $firstPlaceTeams->count();
        $numSecondPlace = $secondPlaceTeams->count();
        
        for ($i = 0; $i < max($numFirstPlace, $numSecondPlace); $i++) {
            // Thêm nhất bảng thứ i
            if ($i < $numFirstPlace) {
                $advancing->push($firstPlaceTeams->get($i));
            }
            
            // Thêm nhì bảng đối diện (từ cuối lên)
            $oppositeIndex = $numSecondPlace - 1 - $i;
            if ($oppositeIndex >= 0 && $oppositeIndex < $numSecondPlace) {
                $advancing->push($secondPlaceTeams->get($oppositeIndex));
            }
        }
        
        // Xử lý các hạng còn lại (nếu có hạng 3, 4...)
        foreach ($advancingByRank as $rank => $teamsAtRank) {
            if ($rank < 2) continue; // Đã xử lý rank 0, 1
            
            foreach ($teamsAtRank as $team) {
                $advancing->push($team);
            }
        }
    
        // ✅ KIỂM TRA SỐ ĐỘI ADVANCING
        $totalAdvancing = $advancing->count();
        $willHaveBye = ($totalAdvancing % 2 !== 0);
        
        // ✅ CHỈ CHO PHÉP BEST LOSER KHI CẦN THIẾT
        if ($willHaveBye && !$advancedToNext) {
            // Padding thêm 1 placeholder rỗng để tránh lỗi
            $advancing->push((object)[
                'team_id' => null,
                '_placeholder' => true,
            ]);
        }
    
        // ===== PHASE 4: TẠO KNOCKOUT STAGE =====
        $knockoutRounds = $this->generateKnockoutStage(
            $type,
            $advancing,
            $hasThirdPlace,
            $advancedToNext,
            $numLegs,
            $matchNumber
        );
    
        // ===== PHASE 5: TẠO POOL ADVANCEMENT RULES =====
        $this->createPoolAdvancementRules($type, $knockoutRounds, $advancing, $groupObjects);
    }

    private function generateKnockoutStage(TournamentType $type, $teams, $hasThirdPlace, $advancedToNext = false, $numLegs = 1, &$matchNumber = 0)
    {
        $teamList = is_array($teams) ? collect($teams) : $teams->values();
        $roundIndex = 2;
        $rounds = collect();
    
        while ($teamList->count() > 1) {
            $matchIds = collect();
            $nextRoundTeams = collect();
            $teamCount = $teamList->count();
            $numMatches = intdiv($teamCount, 2);
            $hasBye = ($teamCount % 2 === 1);
            
            // Tạo các trận đấu bình thường
            for ($i = 0; $i < $numMatches; $i++) {
                $homeIdx = $i * 2;
                $awayIdx = $i * 2 + 1;
                
                $home = $teamList->get($homeIdx);
                $away = $teamList->get($awayIdx);
                $firstMatchId = null;
                
                for ($leg = 1; $leg <= $numLegs; $leg++) {
                    $isReturn = ($leg % 2 === 0);
                    $matchNumber++;
                    
                    $match = $type->matches()->create([
                        'tournament_type_id' => $type->id,
                        'home_team_id' => $isReturn ? $this->getTeamId($away) : $this->getTeamId($home),
                        'away_team_id' => $isReturn ? $this->getTeamId($home) : $this->getTeamId($away),
                        'round' => $roundIndex,
                        'leg' => $leg,
                        'status' => 'pending',
                        'is_bye' => false,
                        'name_of_match' => "Trận đấu số {$matchNumber}",
                    ]);
    
                    if ($leg === 1) {
                        $firstMatchId = $match->id;
                    }
                }
    
                $matchIds->push($firstMatchId);
                
                $nextRoundTeams->push((object)[
                    'team_id' => null,
                    '_from_match' => $firstMatchId,
                ]);
            }
    
            // Xử lý đội bye
            if ($hasBye) {
                $byeTeam = $teamList->get($teamCount - 1);
                $byeTeamId = $this->getTeamId($byeTeam);
    
                if ($advancedToNext) {
                    // ✅ FIX: Tạo trận bye vs best loser và THÊM VÀO $matchIds
                    $firstByeMatchId = null;
                    for ($leg = 1; $leg <= $numLegs; $leg++) {
                        $matchNumber++;
                        $byeMatch = $type->matches()->create([
                            'tournament_type_id' => $type->id,
                            'home_team_id' => $byeTeamId,
                            'away_team_id' => null,
                            'round' => $roundIndex,
                            'leg' => $leg,
                            'status' => 'pending',
                            'is_bye' => true,
                            'best_loser_source_round' => $roundIndex - 1,
                            'name_of_match' => "Trận đấu số {$matchNumber}",
                        ]);
                        if ($leg === 1) {
                            $firstByeMatchId = $byeMatch->id;
                        }
                    }
    
                    // ✅ QUAN TRỌNG: Thêm bye match vào matchIds để nó được link
                    $matchIds->push($firstByeMatchId);
                    
                    $nextRoundTeams->push((object)[
                        'team_id' => null,
                        '_from_match' => $firstByeMatchId,
                    ]);
                } else {
                    // Bye đơn giản (không có best loser)
                    $matchNumber++;
                    $byeMatch = $type->matches()->create([
                        'tournament_type_id' => $type->id,
                        'home_team_id' => $byeTeamId,
                        'away_team_id' => null,
                        'round' => $roundIndex,
                        'leg' => 1,
                        'status' => 'pending',
                        'is_bye' => true,
                        'name_of_match' => "Trận đấu số {$matchNumber}",
                    ]);
    
                    $matchIds->push($byeMatch->id);
                    
                    $nextRoundTeams->push((object)[
                        'team_id' => $byeTeamId,
                        '_bye_match' => $byeMatch,
                    ]);
                }
            }
    
            $rounds->put($roundIndex, $matchIds);
            $teamList = $nextRoundTeams;
            $roundIndex++;
        }
    
        if ($rounds->isEmpty()) {
            return collect();
        }
        
        // Link các trận vào round tiếp theo
        $finalRound = $roundIndex - 1;
        for ($r = 2; $r < $finalRound; $r++) {
            $currMatchIds = $rounds->get($r, collect());
            $nextMatchIds = $rounds->get($r + 1, collect());
            
            foreach ($currMatchIds as $idx => $matchId) {
                $match = $type->matches()->find($matchId);
                if (!$match) continue;
                
                // ✅ FIX: Bỏ điều kiện skip bye match để link được
                // Trước: if ($match->is_bye && !$match->away_team_id) continue;
                // Sau: Cho phép link cả bye match
                
                $targetIdx = intdiv($idx, 2);
                $targetId = $nextMatchIds->get($targetIdx);
                if (!$targetId) continue;
                
                $position = ($idx % 2 === 0) ? 'home' : 'away';
                
                $match->update([
                    'next_match_id' => $targetId,
                    'next_position' => $position,
                ]);
            }
        }
        
        // Xử lý trận tranh hạng 3
        if ($hasThirdPlace) {
            $semiRound = $finalRound - 1;
            $semiIds = $rounds->get($semiRound, collect());
            
            if ($semiIds->count() >= 2) {
                $firstSemiId = $semiIds->get(0);
                $secondSemiId = $semiIds->get(1);
                $firstThirdPlaceId = null;
                
                for ($leg = 1; $leg <= $numLegs; $leg++) {
                    $matchNumber++; 
                    $third = $type->matches()->create([
                        'tournament_type_id' => $type->id,
                        'round' => $finalRound + 1,
                        'leg' => $leg,
                        'is_third_place' => true,
                        'status' => 'pending',
                        'name_of_match' => "Trận đấu số {$matchNumber}",
                    ]);
                
                    if ($leg === 1) {
                        $firstThirdPlaceId = $third->id;
                    }
                }
                
                DB::table('matches')
                    ->where('id', $firstSemiId)
                    ->update([
                        'loser_next_match_id' => $firstThirdPlaceId,
                        'loser_next_position' => 'home',
                    ]);
                DB::table('matches')
                    ->where('id', $secondSemiId)
                    ->update([
                        'loser_next_match_id' => $firstThirdPlaceId,
                        'loser_next_position' => 'away',
                    ]);
            }
        }
    
        return $rounds;
    }

    private function createPoolAdvancementRules(TournamentType $type, $knockoutRounds, $advancing, $groupObjects)
    {
        if (!($knockoutRounds instanceof Collection)) {
            $knockoutRounds = collect($knockoutRounds);
        }
        
        $firstRoundMatchIds = $knockoutRounds->get(2, collect());
        if ($firstRoundMatchIds->isEmpty()) {
            return;
        }

        $numLegs = (int) ($type->num_legs ?? 1);

        $allRound2Matches = Matches::where('tournament_type_id', $type->id)
            ->where('round', 2)
            ->orderBy('id', 'asc') 
            ->get();

        if ($allRound2Matches->isEmpty()) {
            return;
        }
        $matchPairs = $allRound2Matches->chunk($numLegs)->values();
        $knockoutIndex = 0;
        $totalSlots = $matchPairs->count() * 2; // Mỗi cặp (dù 1 hay 2 lượt) vẫn chỉ có 2 vị trí trống (Home/Away)

        foreach ($advancing as $placeholder) {
            if ($knockoutIndex >= $totalSlots) {
                break;
            }
            // Xác định cặp đấu và vị trí (đội thứ 1 vào Home cặp 1, đội thứ 2 vào Away cặp 1,...)
            $pairIndex = intdiv($knockoutIndex, 2);
            $basePosition = ($knockoutIndex % 2 === 0) ? 'home' : 'away';
            
            $matchPair = $matchPairs->get($pairIndex);
            if (!$matchPair) {
                $knockoutIndex++;
                continue;
            }
            if (property_exists($placeholder, '_from_group') && $placeholder->_from_group !== null) {
                $groupId = $placeholder->_from_group;
                $rank = $placeholder->_rank ?? 1;

                foreach ($matchPair as $legMatch) {
                    $isReturnLeg = ($legMatch->leg % 2 === 0);
                    $actualPosition = $isReturnLeg 
                        ? ($basePosition === 'home' ? 'away' : 'home')
                        : $basePosition;

                    PoolAdvancementRule::updateOrCreate([
                        'tournament_type_id' => $type->id,
                        'group_id' => $groupId,
                        'rank' => $rank,
                        'next_match_id' => $legMatch->id,
                    ], [
                        'next_position' => $actualPosition,
                    ]);
                }
            }
            // === XỬ LÝ CÓ SẴN TEAM ID (CHO TRƯỜNG HỢP BYE HOẶC ĐÃ XÁC ĐỊNH) ===
            elseif (property_exists($placeholder, 'team_id') && $placeholder->team_id) {
                foreach ($matchPair as $legMatch) {
                    $isReturnLeg = ($legMatch->leg % 2 === 0);
                    $actualPosition = $isReturnLeg 
                        ? ($basePosition === 'home' ? 'away' : 'home')
                        : $basePosition;

                    $legMatch->update([
                        $actualPosition . '_team_id' => $placeholder->team_id,
                        'status' => 'pending',
                    ]);
                }
            }
            $knockoutIndex++;
        }
    }
    /**
     * Apply pool advancement sau khi hoàn thành vòng bảng
     * Gọi method này từ service khi tất cả matches của pool đã completed
     */
    public function applyPoolAdvancement(TournamentType $type)
    {
        $groups = $type->groups()->with(['matches'])->get();
        
        foreach ($groups as $group) {
            $matches = $group->matches;
            
            // Tính standings
            $standings = TournamentService::calculateGroupStandings($matches);
            
            // ✅ Lấy TẤT CẢ các rules cho group này (bao gồm cả các legs)
            $rules = PoolAdvancementRule::where('group_id', $group->id)
                ->orderBy('rank')
                ->orderBy('next_match_id') // ← Sắp xếp theo match để xử lý tuần tự
                ->get();
            
            // ✅ Group rules theo rank để xử lý từng đội
            $rulesByRank = $rules->groupBy('rank');
            
            foreach ($rulesByRank as $rank => $rulesForRank) {
                // Lấy team theo ranking
                $teamAtRank = $standings->get($rank - 1);
                if (!$teamAtRank) continue;
                
                $advancingTeamId = $teamAtRank['team_id'];
                
                // ✅ Cập nhật TẤT CẢ các legs của đội này
                foreach ($rulesForRank as $rule) {
                    $targetMatch = Matches::find($rule->next_match_id);
                    if (!$targetMatch) continue;
                    
                    $targetMatch->update([
                        $rule->next_position . '_team_id' => $advancingTeamId,
                        'status' => 'pending',
                    ]);
                }
            }
        }
    }
    private function getTeamId($placeholder)
    {
        if (!$placeholder) return null;
        if (is_object($placeholder) && isset($placeholder->team_id)) {
            return $placeholder->team_id;
        }
        return null;
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
        $allMatches = $type->matches()
            ->with(['homeTeam.members', 'awayTeam.members', 'results'])
            ->get();
        $totalRounds = $allMatches->max('round') ?? 1;
    
        // 1. Nhóm theo Round trước để tạo cấu trúc giống Bracket của Elimination
        $rounds = $allMatches->groupBy('round')->map(function ($roundMatches, $round) use ($type, $totalRounds) {
            
            // 2. Trong mỗi Round, nhóm các Leg thành 1 cặp đấu
            $groupedMatches = $roundMatches->groupBy(function ($match) {
                $teams = [$match->home_team_id, $match->away_team_id];
                sort($teams);
                return implode('_', $teams);
            })->values();
    
            return [
                'round' => $round,
                'round_name' => "Vòng " . $round, // Hoặc dùng hàm getRoundName nếu muốn
                'matches' => $groupedMatches->map(function ($legs) use($round, $totalRounds) {
                    $leg1 = $legs->firstWhere('leg', 1) ?? $legs->first();
                    $baseHomeId = $leg1->home_team_id;
                    $baseAwayId = $leg1->away_team_id;
    
                    $homeTotal = 0;
                    $awayTotal = 0;

                    $isFinal = ($round == $totalRounds) && ($legs->count() > 0);
    
                    // 3. Format Legs giống hệt Elimination
                    $formattedLegs = $legs->map(function ($leg) use ($baseHomeId, $baseAwayId, &$homeTotal, &$awayTotal) {
                        $res = $this->calculateSingleMatchWins($leg); // Hàm tính set thắng 2-1, 2-0...
                        
                        $homeLegScore = 0;
                        $awayLegScore = 0;
    
                        // Logic tính điểm thắng (3đ cho thắng trận, 0đ cho thua) giống Elimination
                        if ($leg->home_team_id == $baseHomeId) {
                            $homeLegScore = ($res['home'] > $res['away']) ? 3 : 0;
                            $awayLegScore = ($res['away'] > $res['home']) ? 3 : 0;
                        } else {
                            $homeLegScore = ($res['away'] > $res['home']) ? 3 : 0;
                            $awayLegScore = ($res['home'] > $res['away']) ? 3 : 0;
                        }
    
                        if ($leg->status === 'completed') {
                            $homeTotal += $homeLegScore;
                            $awayTotal += $awayLegScore;
                        }
    
                        return [
                            'id' => $leg->id,
                            'leg' => $leg->leg,
                            'court' => $leg->court,
                            'home_score' => $homeLegScore,
                            'away_score' => $awayLegScore,
                            'status' => $leg->status,
                            'scheduled_at' => $leg->scheduled_at,
                            'is_completed' => $leg->status === 'completed',
                            // Group sets để Modal CreateMatch hiển thị đúng
                            'sets' => $leg->results->groupBy('set_number')->map(function($setGroup) use ($leg) {
                                return $setGroup->map(fn($s) => ['team_id' => $s->team_id, 'score' => $s->score])->values();
                            })
                        ];
                    })->values();
    
                    return [
                        'match_id' => $leg1->id,
                        'home_team' => $this->formatTeam($leg1->homeTeam),
                        'away_team' => $this->formatTeam($leg1->awayTeam),
                        'is_bye' => $leg1->is_bye,
                        'is_final' => $isFinal,
                        'legs' => $formattedLegs,
                        'aggregate_score' => [
                            'home' => $homeTotal,
                            'away' => $awayTotal,
                        ],
                        'status' => $legs->every(fn($l) => $l->status === 'completed') ? 'completed' : 'pending',
                    ];
                })->values()
            ];
        })->values();
    
        return ResponseHelper::success([
            'format' => TournamentType::FORMAT_ROUND_ROBIN,
            'format_type_text' => 'round_robin',
            'bracket' => $rounds, // Dùng key 'bracket' để FE dùng chung logic map
        ]);
    }
    
    // Hàm bổ trợ tính set thắng
    private function calculateSingleMatchWins($match) {
        $homeWins = 0; $awayWins = 0;
        $sets = $match->results->groupBy('set_number');
        foreach ($sets as $set) {
            $h = $set->firstWhere('team_id', $match->home_team_id);
            $a = $set->firstWhere('team_id', '!=', $match->home_team_id);
            if ((int)($h->score ?? 0) > (int)($a->score ?? 0)) $homeWins++;
            elseif ((int)($a->score ?? 0) > (int)($h->score ?? 0)) $awayWins++;
        }
        return ['home' => $homeWins, 'away' => $awayWins];
    }

    /**
     * Elimination - trả về bracket theo round
     */
    private function getEliminationBracket(TournamentType $type)
    {
        // Closure tính điểm và định dạng sets
        $calculateLegDetails = function ($leg) {
            $homeTeamId = $leg->home_team_id;
            $awayTeamId = $leg->away_team_id;
    
            $sets = [];
            $homeSetWins = 0;
            $awaySetWins = 0;
    
            $groupedSets = $leg->results->groupBy('set_number');
    
            foreach ($groupedSets as $setNumber => $setGroup) {
                $home = $setGroup->firstWhere('team_id', $homeTeamId);
                $away = $setGroup->firstWhere('team_id', $awayTeamId);
    
                $homeScore = (int) ($home->score ?? 0);
                $awayScore = (int) ($away->score ?? 0);
    
                if ($homeScore > $awayScore) {
                    $homeSetWins++;
                } elseif ($awayScore > $homeScore) {
                    $awaySetWins++;
                }
    
                $sets['set_' . $setNumber] = [
                    ['team_id' => $homeTeamId, 'score' => $homeScore],
                    ['team_id' => $awayTeamId, 'score' => $awayScore],
                ];
            }
    
            // 👉 QUYẾT ĐỊNH THẮNG LEG
            if ($homeSetWins > $awaySetWins) {
                return [
                    'sets' => $sets,
                    'home_score_calculated' => 3,
                    'away_score_calculated' => 0,
                    'winner_team_id' => $homeTeamId,
                ];
            }
    
            if ($awaySetWins > $homeSetWins) {
                return [
                    'sets' => $sets,
                    'home_score_calculated' => 0,
                    'away_score_calculated' => 3,
                    'winner_team_id' => $awayTeamId,
                ];
            }
    
            // Không đủ dữ liệu → chưa xác định
            return [
                'sets' => $sets,
                'home_score_calculated' => 0,
                'away_score_calculated' => 0,
                'winner_team_id' => null,
            ];
        };
    
        $matches = $type->matches()
            ->with(['homeTeam.members', 'awayTeam.members', 'results'])
            ->orderBy('round')
            ->orderBy('leg')
            ->get();
    
        $maxRound = $matches->max('round') ?? 1;
    
        $bracket = $matches
            ->groupBy('round')
            ->map(function ($roundMatches, $round) use ($calculateLegDetails, $type, $maxRound) {
    
                // ✅ SỬA: Group 2 leg thành 1 match - Dùng match_pair_id hoặc logic ổn định
                $grouped = $roundMatches->groupBy(function ($match) {
                    // Nếu có match_pair_id (nên thêm vào DB)
                    if (isset($match->match_pair_id)) {
                        return 'pair_' . $match->match_pair_id;
                    }
                    
                    // TH1: Dùng next_match_id + next_position (ổn định nhất)
                    if ($match->next_match_id && $match->next_position) {
                        return 'to_' . $match->next_match_id . '_' . $match->next_position;
                    }
                    
                    // TH2: Trận cuối (Final/Third Place) - không có next
                    if ($match->is_third_place) {
                        return 'third_place_' . $match->round;
                    }
                    
                    if (!$match->next_match_id) {
                        return 'final_' . $match->round;
                    }
                    
                    // TH3: Fallback - gom theo min ID của 2 leg
                    // Leg 1 & 2 thường có ID liên tiếp
                    $baseId = floor($match->id / 2) * 2;
                    return 'match_' . $baseId;
                })->values();
    
                // ✅ XỬ LÝ TÊN ROUND
                $roundName = $this->getRoundName($round, $grouped->count(), $type->format);
                
                // ✅ NẾU TẤT CẢ MATCHES TRONG ROUND LÀ THIRD PLACE
                if ($roundMatches->every(fn($m) => $m->is_third_place ?? false)) {
                    $roundName = 'Tranh hạng Ba';
                }
    
                return [
                    'round' => $round,
                    'round_name' => $roundName,
                    'matches' => $grouped->map(function ($matchGroup) use ($calculateLegDetails, $round, $maxRound) {
    
                        $first = $matchGroup->first();
                        $homeTeamId = $first->home_team_id;
                        $awayTeamId = $first->away_team_id;
    
                        $homeTotal = 0;
                        $awayTotal = 0;
    
                        // ✅ KIỂM TRA IS_FINAL
                        $isFinal = ($round == $maxRound) && !($first->is_third_place ?? false);
    
                        $legs = $matchGroup->map(function ($leg) use (
                            $calculateLegDetails,
                            &$homeTotal,
                            &$awayTotal,
                            $homeTeamId,
                            $awayTeamId
                        ) {
                            $details = $calculateLegDetails($leg);
                            if ($leg->status === 'completed') {
                                if ($details['winner_team_id'] === $homeTeamId) {
                                    $homeTotal += 3;
                                } elseif ($details['winner_team_id'] === $awayTeamId) {
                                    $awayTotal += 3;
                                }
                            }
    
                            return [
                                'id' => $leg->id,
                                'leg' => $leg->leg,
                                'court' => $leg->court,
                                'home_score' => $details['home_score_calculated'],
                                'away_score' => $details['away_score_calculated'],
                                'status' => $leg->status,
                                'scheduled_at' => $leg->scheduled_at,
                                'is_completed' => $leg->status === 'completed',
                                'sets' => $details['sets'],
                            ];
                        })->values();
    
                        return [
                            'match_id' => $first->id,
                            'home_team' => $this->formatTeam($first->homeTeam),
                            'away_team' => $this->formatTeam($first->awayTeam),
                            'is_bye' => $first->is_bye,
                            'is_third_place' => $first->is_third_place ?? false,
                            'is_final' => $isFinal, // ✅ THÊM FLAG
                            'legs' => $legs,
                            'aggregate_score' => [
                                'home' => $homeTotal,
                                'away' => $awayTotal,
                            ],
                            'winner_team_id' =>
                                $homeTotal > $awayTotal ? $homeTeamId :
                                ($awayTotal > $homeTotal ? $awayTeamId : null),
                            'next_match_id' => $first->next_match_id,
                            'next_position' => $first->next_position,
                        ];
                    })->values(),
                ];
            })->values();
    
        return ResponseHelper::success([
            'format' => TournamentType::FORMAT_ELIMINATION,
            'format_type_text' => 'elimination',
            'bracket' => $bracket,
            'total_rounds' => $bracket->count(),
        ]);
    }

    private function getMixedBracket(TournamentType $type)
    {
        $calculateLegDetails = function ($leg) {
            $homeTeamId = $leg->home_team_id;
            $awayTeamId = $leg->away_team_id;
    
            $sets = [];
            $homeSetWins = 0;
            $awaySetWins = 0;
    
            $groupedSets = $leg->results->groupBy('set_number');
    
            foreach ($groupedSets as $setNumber => $setGroup) {
                $home = $setGroup->firstWhere('team_id', $homeTeamId);
                $away = $setGroup->firstWhere('team_id', $awayTeamId);
    
                $homeScore = (int) ($home->score ?? 0);
                $awayScore = (int) ($away->score ?? 0);
    
                if ($homeScore > $awayScore) $homeSetWins++;
                elseif ($awayScore > $homeScore) $awaySetWins++;
    
                $sets['set_' . $setNumber] = [
                    ['team_id' => $homeTeamId, 'score' => $homeScore],
                    ['team_id' => $awayTeamId, 'score' => $awayScore],
                ];
            }
    
            if ($homeSetWins > $awaySetWins) {
                return [
                    'sets' => $sets,
                    'home_score_calculated' => 3,
                    'away_score_calculated' => 0,
                    'winner_team_id' => $homeTeamId,
                ];
            }
    
            if ($awaySetWins > $homeSetWins) {
                return [
                    'sets' => $sets,
                    'home_score_calculated' => 0,
                    'away_score_calculated' => 3,
                    'winner_team_id' => $awayTeamId,
                ];
            }
    
            return [
                'sets' => $sets,
                'home_score_calculated' => 0,
                'away_score_calculated' => 0,
                'winner_team_id' => null,
            ];
        };
    
        // ===== POOL STAGE =====
        $poolMatches = $type->matches()
            ->with(['homeTeam.members', 'awayTeam.members', 'group', 'results'])
            ->where('round', 1)
            ->orderBy('group_id')
            ->orderBy('leg')
            ->get();
    
        $poolStage = $poolMatches->groupBy('group_id')->map(function ($groupMatches, $groupId) use ($calculateLegDetails) {
            $group = $groupMatches->first()->group;
    
            $grouped = $groupMatches->groupBy(function ($match) {
                if (!$match->home_team_id && !$match->away_team_id) {
                    return 'match_' . $match->id;
                }
    
                return collect([
                    $match->home_team_id,
                    $match->away_team_id,
                ])->sort()->implode('_');
            })->values();
    
            return [
                'group_id' => $groupId,
                'group_name' => $group?->name ?? 'Bye',
                'matches' => $grouped->map(function ($matchGroup) use ($calculateLegDetails) {
                    $first = $matchGroup->first();
                    $homeTeamId = $first->home_team_id;
                    $awayTeamId = $first->away_team_id;
    
                    $homeTotal = 0;
                    $awayTotal = 0;
    
                    $legs = $matchGroup->map(function ($leg) use (
                        $calculateLegDetails,
                        &$homeTotal,
                        &$awayTotal,
                        $homeTeamId,
                        $awayTeamId
                    ) {
                        $details = $calculateLegDetails($leg);
    
                        if ($leg->status === 'completed') {
                            if ($details['winner_team_id'] === $homeTeamId) $homeTotal += 3;
                            elseif ($details['winner_team_id'] === $awayTeamId) $awayTotal += 3;
                        }
    
                        return [
                            'id' => $leg->id,
                            'leg' => $leg->leg,
                            'court' => $leg->court,
                            'home_score' => $details['home_score_calculated'],
                            'away_score' => $details['away_score_calculated'],
                            'status' => $leg->status,
                            'scheduled_at' => $leg->scheduled_at,
                            'is_completed' => $leg->status === 'completed',
                            'sets' => $details['sets'],
                        ];
                    })->values();
    
                    return [
                        'match_id' => $first->id,
                        'home_team' => $this->formatTeam($first->homeTeam),
                        'away_team' => $this->formatTeam($first->awayTeam),
                        'is_bye' => $first->is_bye,
                        'is_final' => false, // ✅ Pool stage không có final
                        'legs' => $legs,
                        'aggregate_score' => [
                            'home' => $homeTotal,
                            'away' => $awayTotal,
                        ],
                        'winner_team_id' =>
                            $homeTotal > $awayTotal ? $homeTeamId :
                            ($awayTotal > $homeTotal ? $awayTeamId : null),
                        'status' => $matchGroup->every(fn ($l) => $l->status === 'completed') ? 'completed' : 'pending',
                    ];
                })->values(),
                'standings' => $this->calculateGroupStandings($groupMatches),
            ];
        })->values();
    
        // ===== ADVANCEMENT RULES =====
        $advancementRules = PoolAdvancementRule::where('tournament_type_id', $type->id)
            ->with('group')
            ->get()
            ->groupBy('next_match_id');
    
        // ===== KNOCKOUT STAGE =====
        $knockoutMatches = $type->matches()
            ->with(['homeTeam.members', 'awayTeam.members', 'results'])
            ->where('round', '>=', 2)
            ->orderBy('round')
            ->orderBy('leg')
            ->get();
    
        $maxKnockoutRound = $knockoutMatches->max('round') ?? 2;
    
        $knockoutStage = $knockoutMatches->groupBy('round')->map(function ($roundMatches, $round) use (
            $calculateLegDetails,
            $type,
            $advancementRules,
            $maxKnockoutRound
        ) {
            $numLegs = (int) ($type->num_legs ?? 1);
            $sortedMatches = $roundMatches->sortBy('id')->values();
            $matchGroups = $sortedMatches->chunk($numLegs);
    
            $roundName = $this->getRoundName($round, $matchGroups->count(), $type->format);
            
            if ($roundMatches->every(fn($m) => $m->is_third_place ?? false)) {
                $roundName = 'Tranh hạng Ba';
            }
    
            return [
                'round' => $round,
                'round_name' => $roundName,
                'matches' => $matchGroups->map(function ($matchGroup) use (
                    $calculateLegDetails,
                    $advancementRules,
                    $round,
                    $maxKnockoutRound
                ) {
                    $first = $matchGroup->first();
    
                    $homeTeamId = $first->home_team_id;
                    $awayTeamId = $first->away_team_id;
    
                    // ===== PLACEHOLDER LOGIC ĐÚNG THEO Ý MÀY =====
                    $rulesForThisMatch = $advancementRules->get($first->id, collect());
    
                    $homePlaceholder = null;
                    $awayPlaceholder = null;
    
                    foreach ($rulesForThisMatch as $rule) {
                        $text = trim(
                            $this->getRankText($rule->rank) . ' ' . ($rule->group?->name ?? '')
                        );
    
                        if ($rule->next_position === 'home') $homePlaceholder = $text;
                        if ($rule->next_position === 'away') $awayPlaceholder = $text;
                    }
    
                    // 👉 RULE BỊ LẺ ⇒ SINH "NHÌ TỐT NHẤT"
                    if ($rulesForThisMatch->count() === 1) {
                        $onlyRule = $rulesForThisMatch->first();
    
                        if ($onlyRule->next_position === 'home' && !$awayPlaceholder) {
                            $awayPlaceholder = 'Nhì tốt nhất';
                        }
    
                        if ($onlyRule->next_position === 'away' && !$homePlaceholder) {
                            $homePlaceholder = 'Nhì tốt nhất';
                        }
                    }
    
                    $homeTotal = 0;
                    $awayTotal = 0;
    
                    // ✅ KIỂM TRA IS_FINAL
                    $isFinal = ($round == $maxKnockoutRound) && !($first->is_third_place ?? false);
    
                    $legs = $matchGroup->map(function ($leg) use (
                        $calculateLegDetails,
                        &$homeTotal,
                        &$awayTotal,
                        $homeTeamId,
                        $awayTeamId
                    ) {
                        $details = $calculateLegDetails($leg);
    
                        if ($leg->status === 'completed') {
                            if ($details['winner_team_id'] === $homeTeamId) $homeTotal += 3;
                            elseif ($details['winner_team_id'] === $awayTeamId) $awayTotal += 3;
                        }
    
                        return [
                            'id' => $leg->id,
                            'leg' => $leg->leg,
                            'court' => $leg->court,
                            'home_score' => $details['home_score_calculated'],
                            'away_score' => $details['away_score_calculated'],
                            'status' => $leg->status,
                            'scheduled_at' => $leg->scheduled_at,
                            'is_completed' => $leg->status === 'completed',
                            'sets' => $details['sets'],
                        ];
                    })->values();
    
                    $finalWinnerId = null;
                    if ($matchGroup->every(fn ($l) => $l->status === 'completed')) {
                        if ($homeTotal > $awayTotal) $finalWinnerId = $homeTeamId;
                        elseif ($awayTotal > $homeTotal) $finalWinnerId = $awayTeamId;
                    }
    
                    return [
                        'match_id' => $first->id,
                        'home_team' => $this->formatTeam($first->homeTeam, $homePlaceholder),
                        'away_team' => $this->formatTeam($first->awayTeam, $awayPlaceholder),
                        'is_bye' => $first->is_bye,
                        'is_third_place' => $first->is_third_place ?? false,
                        'is_final' => $isFinal, // ✅ THÊM FLAG
                        'legs' => $legs,
                        'aggregate_score' => [
                            'home' => $homeTotal,
                            'away' => $awayTotal,
                        ],
                        'winner_team_id' => $finalWinnerId,
                        'status' => $matchGroup->every(fn ($l) => $l->status === 'completed') ? 'completed' : 'pending',
                    ];
                })->values(),
            ];
        })->values();
    
        return ResponseHelper::success([
            'format' => TournamentType::FORMAT_MIXED,
            'format_type_text' => 'mixed',
            'pool_stage' => $poolStage,
            'knockout_stage' => $knockoutStage,
        ]);
    }
    
    private function getRankText(int $rank): string
    {
        return match($rank) {
            1 => 'Nhất',
            2 => 'Nhì', 
            3 => 'Ba',
            4 => 'Tư',
            default => "Hạng {$rank}",
        };
    }
    
    /**
     * Format team data
     */
    private function formatTeam($team, $placeholderText = null)
    {
        return TournamentService::formatTeam($team, $placeholderText);
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
                // $homeTotal += $match->home_score ?? 0;
                // $awayTotal += $match->away_score ?? 0;
                if($match->winner_id === $match->away_team_id){
                    $awayTotal += 3;
                }else {
                    $homeTotal += 3;
                }
            }
        }

        return [
            'home' => $homeTotal,
            'away' => $awayTotal,
        ];
    }

    /**
     * Tính bảng xếp hạng cho group
     */
    private function calculateGroupStandings($groupMatches)
    {
        return TournamentService::calculateGroupStandings($groupMatches);
    }

    /**
     * Lấy tên round
     */
    private function getRoundName($round, $pairCount, $format)
    {
        if ($round === 1 && $format == TournamentType::FORMAT_MIXED) {
            return 'Vòng bảng';
        }

        return match ($pairCount) {
            1 => 'Chung kết',
            2 => 'Bán kết',
            4 => 'Tứ kết',
            8 => 'Vòng 1/8',
            16 => 'Vòng 1/16',
            32 => 'Vòng 1/32',
            default => "Vòng {$round}",
        };
    }

    public function getRank($tournament_id)
    {
        $type = TournamentType::where('tournament_id', $tournament_id)->first();
        if (!$type) {
            return ResponseHelper::error('Tournament type not found', 404);
        }

        // Lấy bảng xếp hạng đã được tính toán sẵn từ database
        // Bảng này đã được sắp xếp theo đúng rankingRules (4, 1, 3...)
        $savedRankings = TeamRanking::where('tournament_type_id', $type->id)
            ->orderBy('rank', 'asc')
            ->with(['team.members'])
            ->get();

        $groups = $type->groups()->get();

        // TH 1: Nếu không chia bảng (Tính rank chung)
        if ($groups->isEmpty()) {
            $data = $savedRankings->map(function ($r) use ($type) {
                // Lấy stats chi tiết nếu cần hiển thị (giống trong recalculate)
                $stats = $this->getTeamStats($r->team_id, $type->id);
                return array_merge([
                    'rank' => $r->rank,
                    'team_name' => $r->team->name ?? 'Unknown',
                    'team_avatar' => $r->team->avatar ??'',
                ], $stats);
            });

            return ResponseHelper::success(['rankings' => $data]);
        }

        // TH 2: Nếu có chia bảng
        $groupRankings = $groups->map(function ($group) use ($type, $savedRankings) {
            // Lọc ra các đội thuộc group này từ bảng rank đã sắp xếp
            $rankInGroup = $savedRankings->filter(function($r) use ($group, $type) {
                // Kiểm tra đội có trận đấu nào trong group này không
                return Matches::where('group_id', $group->id)
                    ->where(function($q) use ($r) {
                        $q->where('home_team_id', $r->team_id)
                        ->orWhere('away_team_id', $r->team_id);
                    })->exists();
            })->values();

            $rankings = $rankInGroup->map(function ($r, $index) use ($type) {
                $stats = $this->getTeamStats($r->team_id, $type->id);
                return array_merge([
                    'rank' => $index + 1, // Đánh lại hạng trong nội bộ bảng
                    'team_name' => $r->team->name ?? 'Unknown',
                    'team_avatar' => $r->team->avatar ?? '',
                ], $stats);
            });

            return [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'rankings' => $rankings,
            ];
        });

        return ResponseHelper::success(['group_rankings' => $groupRankings]);
    }

    /**
     * Hàm bổ trợ để lấy các chỉ số thắng/thua/hiệu số để hiển thị
     */
    private function getTeamStats($teamId, $tournamentTypeId)
    {
        $matches = Matches::where('tournament_type_id', $tournamentTypeId)
            ->where('status', 'completed')
            ->where(function ($query) use ($teamId) {
                $query->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
            })
            ->with('results')
            ->get();

        $wins = $matches->where('winner_id', $teamId)->count();
        $played = $matches->count();
        
        $pWon = 0;
        $pLost = 0;
        foreach($matches as $m) {
            $pWon += $m->results->where('team_id', $teamId)->sum('score');
            $opponentResult = $m->results->where('team_id', '!=', $teamId)->first();
            if ($opponentResult) $pLost += $opponentResult->score;
        }

        return [
            'team_id' => $teamId,
            'played' => $played,
            'wins' => $wins,
            'losses' => $played - $wins,
            'points' => $wins * 3,
            'point_diff' => $pWon - $pLost,
            'win_rate' => $played > 0 ? round(($wins / $played) * 100, 2) : 0,
        ];
    }
    public function getAdvancementStatus(TournamentType $tournamentType)
    {
        if ($tournamentType->format !== TournamentType::FORMAT_MIXED) {
            return ResponseHelper::error('Chỉ áp dụng cho format Mixed', 400);
        }

        $groups = $tournamentType->groups()->with(['matches.homeTeam', 'matches.awayTeam'])->get();

        $groupStatus = $groups->map(function ($group) use ($tournamentType) {
            $matches = $group->matches;
            $totalMatches = $matches->count();
            $completedMatches = $matches->where('status', 'completed')->count();
            $isCompleted = $totalMatches > 0 && $completedMatches === $totalMatches;

            $rules = PoolAdvancementRule::where('group_id', $group->id)
                ->with('nextMatch')
                ->orderBy('rank')
                ->get()
                ->map(function ($rule) use ($isCompleted, $matches) {
                    $advancedTeam = null;

                    if ($isCompleted) {
                        $standings = TournamentService::calculateGroupStandings($matches);
                        $teamAtRank = $standings->get($rule->rank - 1);
                        $advancedTeam = $teamAtRank ? $teamAtRank['team'] : null;
                    }

                    return [
                        'rank' => $rule->rank,
                        'next_match_id' => $rule->next_match_id,
                        'next_position' => $rule->next_position,
                        'team' => $advancedTeam,
                        'is_advanced' => $isCompleted && $advancedTeam !== null,
                    ];
                });

            return [
                'group_id' => $group->id,
                'group_name' => $group->name,
                'total_matches' => $totalMatches,
                'completed_matches' => $completedMatches,
                'progress_percent' => $totalMatches > 0 ? round(($completedMatches / $totalMatches) * 100, 1) : 0,
                'is_completed' => $isCompleted,
                'advancement_rules' => $rules,
            ];
        });

        $overallCompleted = $groupStatus->every(fn($g) => $g['is_completed']);

        return ResponseHelper::success([
            'tournament_type_id' => $tournamentType->id,
            'groups' => $groupStatus,
            'all_pools_completed' => $overallCompleted,
            'knockout_ready' => $overallCompleted,
        ]);
    }

    public function regenerateMatches(TournamentType $tournamentType)
    {
        $completedMatches = $tournamentType->matches()
            ->where('status', 'completed')
            ->exists();

        if ($completedMatches) {
            return ResponseHelper::error(
                'Không thể chia lại cặp đấu. Đã có trận đấu hoàn thành thuộc thể thức này.', 
                400
            );
        }

        DB::beginTransaction();
        try {
            $tournamentType->load('tournament.teams.members');
            if ($tournamentType->format == TournamentType::FORMAT_MIXED) {
                $tournamentType->advancementRules()->delete();
                $tournamentType->groups()->delete();
            }
            $tournamentType->matches()->each(function ($match) {
                $match->results()->delete();
                $match->delete();
            });        
            $this->generateMatchesForType($tournamentType);

            DB::commit();
            return ResponseHelper::success(
                new TournamentTypeResource($tournamentType->fresh()), 
                'Chia lại cặp đấu thành công.'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseHelper::error('Lỗi khi chia lại cặp đấu: ' . $e->getMessage(), 500);
        }
    }
    /**
     * Lưu đội vào các bảng và generate matches
     * POST /api/tournament-types/{tournamentType}/assign-teams-and-generate
     */
    public function assignTeamsAndGenerate(Request $request, TournamentType $tournamentType)
    {
        $validated = $request->validate([
            'groups' => 'required|array',
            'groups.*.group_id' => 'required|exists:groups,id',
            'groups.*.team_ids' => 'required|array|min:1',
            'groups.*.team_ids.*' => 'exists:teams,id',
        ]);

        // Kiểm tra có trận đã completed không
        $completedMatches = $tournamentType->matches()
            ->where('status', 'completed')
            ->exists();

        if ($completedMatches) {
            return ResponseHelper::error(
                'Không thể sắp xếp lại. Đã có trận đấu hoàn thành.', 
                400
            );
        }

        DB::beginTransaction();
        try {
            // 1. Xóa các assignment cũ và matches cũ
            foreach ($tournamentType->groups as $group) {
                $group->teams()->detach();
            }
            $tournamentType->matches()->each(function ($match) {
                $match->results()->delete();
                $match->delete();
            });
            if ($tournamentType->format == TournamentType::FORMAT_MIXED) {
                $tournamentType->advancementRules()->delete();
            }

            // 2. Gán đội vào các bảng
            foreach ($validated['groups'] as $groupData) {
                $group = Group::find($groupData['group_id']);
                
                // Sync teams với order
                $syncData = [];
                foreach ($groupData['team_ids'] as $order => $teamId) {
                    $syncData[$teamId] = ['order' => $order];
                }
                $group->teams()->sync($syncData);
            }
            $this->generateMatchesForTypeWithAssignedTeams($tournamentType);

            DB::commit();
            return ResponseHelper::success(
                new TournamentTypeResource($tournamentType->fresh()), 
                'Sắp xếp đội và tạo lịch thi đấu thành công'
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return ResponseHelper::error('Lỗi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generate matches với đội đã được assign vào bảng
     */
    protected function generateMatchesForTypeWithAssignedTeams(TournamentType $type)
    {
        $config = $type->format_specific_config ?? [];
        $numLegs = $type->num_legs ?? 1;

        switch ($type->format) {
            case TournamentType::FORMAT_ROUND_ROBIN:
                $teams = $type->tournament->teams()->with('members')->get();
                $this->generateRoundRobin($type, $teams, $numLegs);
                break;

            case TournamentType::FORMAT_ELIMINATION:
                $teams = $type->tournament->teams()->with('members')->get();
                $this->generateElimination($type, $teams, $config, $numLegs);
                break;

            case TournamentType::FORMAT_MIXED:
                $this->generateMixedWithAssignedTeams($type, $config, $numLegs);
                break;
        }
    }

    private function generateMixedWithAssignedTeams(TournamentType $type, $config, $numLegs)
    {
        $matchNumber = 0;
        $mainConfig = is_array($config) && isset($config[0]) ? $config[0] : [];
        $poolConfig = $mainConfig['pool_stage'] ?? [];
        $numAdvancing = max(1, (int)($poolConfig['num_advancing_teams'] ?? 1));
        $advancedToNext = filter_var($mainConfig['advanced_to_next_round'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $hasThirdPlace = filter_var($mainConfig['has_third_place_match'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $groups = $type->groups()->with('teams.members')->get();
        $advancingByRank = collect();
        $groupObjects = collect();

        // ===== PHASE 2: TẠO VÒNG BẢNG =====
        foreach ($groups as $group) {
            $groupObjects->push($group);
            
            // ✅ LẤY ĐỘI TỪ PIVOT TABLE
            $chunk = $group->teams; // Đã có relation và orderBy
            $count = $chunk->count();

            if ($count === 1) {
                $matchNumber++;
                $byeMatch = $type->matches()->create([
                    'tournament_type_id' => $type->id,
                    'home_team_id' => $chunk[0]->id,
                    'away_team_id' => null,
                    'round' => 1,
                    'leg' => 1,
                    'is_bye' => true,
                    'status' => 'pending',
                    'name_of_match' => "Trận đấu số {$matchNumber}",
                ]);
                
                if (!isset($advancingByRank[0])) {
                    $advancingByRank[0] = collect();
                }
                $advancingByRank[0]->push((object)[
                    'team_id' => $chunk[0]->id,
                    '_bye_match' => $byeMatch,
                    '_group_id' => null,
                    '_group_index' => $groups->search($group),
                    '_rank' => 1,
                ]);
                continue;
            }

            // ✅ ROUND ROBIN VỚI ĐÚNG THỨ TỰ ĐÃ ASSIGN
            $scheduleTeams = $chunk->pluck('id')->toArray();
            $isOdd = $count % 2 !== 0;
            if ($isOdd) {
                $scheduleTeams[] = 'BYE';
                $count++;
            }
            $totalRounds = $count - 1;

            for ($leg = 1; $leg <= $numLegs; $leg++) {
                $currentSchedule = $scheduleTeams;

                for ($round = 1; $round <= $totalRounds; $round++) {
                    $halfSize = $count / 2;
                    $homeTeams = array_slice($currentSchedule, 0, $halfSize);
                    $awayTeams = array_reverse(array_slice($currentSchedule, $halfSize));

                    for ($i = 0; $i < $halfSize; $i++) {
                        $homeId = $homeTeams[$i];
                        $awayId = $awayTeams[$i];

                        if ($homeId === 'BYE' || $awayId === 'BYE') {
                            continue;
                        }

                        $matchNumber++;
                        $isReturnLeg = ($leg % 2 === 0);
                        $finalHomeId = $isReturnLeg ? $awayId : $homeId;
                        $finalAwayId = $isReturnLeg ? $homeId : $awayId;

                        $type->matches()->create([
                            'group_id' => $group->id,
                            'tournament_type_id' => $type->id,
                            'home_team_id' => $finalHomeId,
                            'away_team_id' => $finalAwayId,
                            'round' => 1,
                            'leg' => $leg,
                            'is_bye' => false,
                            'status' => 'pending',
                            'name_of_match' => "Trận đấu số {$matchNumber}",
                        ]);
                    }

                    $firstTeam = array_shift($currentSchedule);
                    $lastTeam = array_pop($currentSchedule);
                    array_unshift($currentSchedule, $firstTeam, $lastTeam);
                }
            }

            // Thu thập placeholder
            for ($k = 0; $k < min($numAdvancing, $chunk->count()); $k++) {
                if (!isset($advancingByRank[$k])) {
                    $advancingByRank[$k] = collect();
                }
                
                $advancingByRank[$k]->push((object)[
                    'team_id' => null,
                    '_from_group' => $group->id,
                    '_group_index' => $groups->search($group),
                    '_rank' => $k + 1,
                ]);
            }
        }

        // ===== PHASE 3 & 4: Cross-matching và Knockout =====
        // ... giữ nguyên logic cũ ...
        $advancing = collect();
        $firstPlaceTeams = $advancingByRank->get(0, collect());
        $secondPlaceTeams = $advancingByRank->get(1, collect());
        
        $numFirstPlace = $firstPlaceTeams->count();
        $numSecondPlace = $secondPlaceTeams->count();
        
        for ($i = 0; $i < max($numFirstPlace, $numSecondPlace); $i++) {
            if ($i < $numFirstPlace) {
                $advancing->push($firstPlaceTeams->get($i));
            }
            
            $oppositeIndex = $numSecondPlace - 1 - $i;
            if ($oppositeIndex >= 0 && $oppositeIndex < $numSecondPlace) {
                $advancing->push($secondPlaceTeams->get($oppositeIndex));
            }
        }
        
        foreach ($advancingByRank as $rank => $teamsAtRank) {
            if ($rank < 2) continue;
            foreach ($teamsAtRank as $team) {
                $advancing->push($team);
            }
        }

        $totalAdvancing = $advancing->count();
        $willHaveBye = ($totalAdvancing % 2 !== 0);
        
        if ($willHaveBye && !$advancedToNext) {
            $advancing->push((object)[
                'team_id' => null,
                '_placeholder' => true,
            ]);
        }

        $knockoutRounds = $this->generateKnockoutStage(
            $type,
            $advancing,
            $hasThirdPlace,
            $advancedToNext,
            $numLegs,
            $matchNumber
        );

        $this->createPoolAdvancementRules($type, $knockoutRounds, $advancing, $groupObjects);
    }

    /**
     * Lấy groups với teams đã assign (nếu có)
     * GET /api/tournament-types/{tournamentType}/groups-with-teams
     */
    public function getGroupsWithTeams(TournamentType $tournamentType)
    {
        if ($tournamentType->format !== TournamentType::FORMAT_MIXED) {
            return ResponseHelper::error('Chỉ áp dụng cho format Mixed', 400);
        }

        $groups = $tournamentType->groups()->with('teams.members')->get();
        
        // ✅ NẾU CHƯA CÓ BẢNG -> TẠO MỚI
        if ($groups->isEmpty()) {
            $this->createEmptyGroups($tournamentType);
            $groups = $tournamentType->groups()->with('teams.members')->get();
        }

        $availableTeams = $tournamentType->tournament->teams()
            ->with('members')
            ->whereNotIn('id', function($query) use ($tournamentType) {
                $query->select('team_id')
                    ->from('group_team')
                    ->whereIn('group_id', $tournamentType->groups()->pluck('id'));
            })
            ->get();
    
        // ✅ LẤY SPORT_ID TỪ TOURNAMENT
        $sportId = $tournamentType->tournament->sport_id ?? null;
    
        return ResponseHelper::success([
            'groups' => $groups->map(fn($g) => [
                'group_id' => $g->id,
                'group_name' => $g->name,
                'teams' => $g->teams->map(fn($t) => [
                    'team_id' => $t->id,
                    'team_name' => $t->name,
                    'team_avatar' => $t->avatar,
                    'vndupr_avg' => $this->calculateTeamVnduprAvg($t, $sportId), // ✅ THÊM VNDUPR
                    'members' => $t->members->map(fn($m) => [
                        'user_id' => $m->user_id ?? $m->id ?? null,
                        'full_name' => $m->full_name ?? $m->user->full_name ?? 'Unknown',
                        'avatar_url' => $m->avatar_url ?? $m->user->avatar_url ?? null,
                    ]),
                ]),
            ]),
            'available_teams' => $availableTeams->map(fn($t) => [
                'team_id' => $t->id,
                'team_name' => $t->name,
                'team_avatar' => $t->avatar,
                'vndupr_avg' => $this->calculateTeamVnduprAvg($t, $sportId), // ✅ THÊM VNDUPR
                'members' => $t->members->map(fn($m) => [
                    'user_id' => $m->user_id ?? $m->id ?? null,
                    'full_name' => $m->full_name ?? $m->user->full_name ?? 'Unknown',
                    'avatar_url' => $m->avatar_url ?? $m->user->avatar_url ?? null,
                ]),
            ]),
            'config' => [
                'num_groups' => $groups->count(),
                'num_advancing' => $tournamentType->format_specific_config[0]['pool_stage']['num_advancing_teams'] ?? 1,
            ]
        ]);
    }
    /**
     * ✅ HÀM TÍNH ĐIỂM VNDUPR TRUNG BÌNH CỦA ĐỘI
     */
    private function calculateTeamVnduprAvg($team, $sportId)
    {
        if (!$sportId) {
            return null;
        }

        // ✅ DEBUG: Kiểm tra cấu trúc members
        // dd($team->members->first());

        // ✅ XỬ LÝ NHIỀU TRƯỜNG HỢP RELATION
        $userIds = collect($team->members)
            ->map(function($member) {
                // TH1: Member là User trực tiếp (hasMany users)
                if (isset($member->id) && !isset($member->user_id)) {
                    return $member->id;
                }
                // TH2: Member là pivot/intermediate table (belongsToMany)
                if (isset($member->user_id)) {
                    return $member->user_id;
                }
                // TH3: Member có relation user
                if (isset($member->user) && isset($member->user->id)) {
                    return $member->user->id;
                }
                return null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($userIds)) {
            return null;
        }

        // Query điểm VNDUPR từ database
        $scores = DB::table('user_sport as us')
            ->join('user_sport_scores as uss', 'us.id', '=', 'uss.user_sport_id')
            ->where('us.sport_id', $sportId)
            ->where('uss.score_type', 'vndupr_score')
            ->whereIn('us.user_id', $userIds)
            ->pluck('uss.score_value', 'us.user_id')
            ->map(fn($v) => (float)$v)
            ->values()
            ->all();

        if (empty($scores)) {
            return null;
        }

        // Tính trung bình
        $average = array_sum($scores) / count($scores);
        
        return round($average, 2);
    }
}