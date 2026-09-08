<?php

namespace App\Services\TournamentType;

use App\Models\Group;
use App\Models\Matches;
use App\Models\MatchResult;
use App\Models\Team;
use App\Models\TournamentType;
use App\Services\TournamentService;
use Illuminate\Support\Collection;

/**
 * Service tính toán CROSS-GROUP COMPARISON RANKING (read-only).
 *
 * Mục đích:
 * - Xét các đội Nhì/Ba giữa các bảng khi số đội trong các bảng không đều.
 * - Loại các trận gặp đội xếp cuối bảng khỏi thành tích dùng để so sánh Nhì/Ba.
 * - KHÔNG thay đổi Matches / MatchResult / Group standings — chỉ tính toán layer riêng.
 *
 * Phụ thuộc:
 * - CrossGroupRankingService: kiểm tra applicability runtime (format MIXED, >=2 bảng,
 *   số đội không đều, enabled=true).
 * - TournamentService::calculateGroupStandings: tính BXH thật của từng bảng
 *   (tái sử dụng — không duplicate logic).
 */
class CrossGroupComparisonService
{
    public const CANDIDATE_TYPE_RUNNER_UP = 'runner_up';
    public const CANDIDATE_TYPE_THIRD_PLACE = 'third_place';

    /** Một trận đã hoàn thành có tỷ số đầy đủ → counted cho comparison. */
    protected const STATUS_COMPLETED = 'completed';

    public function __construct(
        private readonly CrossGroupRankingService $crossGroupRankingService
    ) {}

    /**
     * Trả về cấu trúc chuẩn khi rule không áp dụng.
     */
    public function notAppliedResponse(): array
    {
        return [
            'enabled' => false,
            'applied' => false,
            'comparison_rule' => [
                'minimum_group_size' => null,
                'description' => null,
            ],
            'knockout_calculation' => [
                'num_advancing_per_group' => 0,
                'number_of_groups' => 0,
                'total_from_pool_stage' => 0,
                'is_power_of_two' => false,
                'knockout_slots' => 0,
                'additional_slots' => 0,
            ],
            'qualification' => [
                'number_of_groups' => 0,
                'knockout_slots' => 0,
                'additional_slots' => 0,
                'runner_up_candidates' => 0,
                'third_place_candidates' => 0,
            ],
            'candidates' => [],
        ];
    }

    /**
     * Evaluate applicability + build toàn bộ payload cho API 1.
     *
     * @return array{enabled:bool, applied:bool, comparison_rule:array, qualification:array, candidates:array}
     */
    public function buildComparisonPayload(TournamentType $type): array
    {
        $rawConfig = $this->extractCrossGroupRankingConfig($type);
        $evaluation = $this->crossGroupRankingService->evaluate($type, $rawConfig);

        if (!$evaluation['applied']) {
            $payload = $this->notAppliedResponse();
            // Vẫn truyền enabled flag để FE biết có đang bật setting hay không.
            $payload['enabled'] = $evaluation['enabled'];
            return $payload;
        }

        $minimumGroupSize = (int) $evaluation['minimum_group_size'];
        $groups = $type->groups()->orderBy('id')->get();
        $groupTeamCounts = $evaluation['group_team_counts'];

        $candidates = $this->buildCandidates($groups, $minimumGroupSize);

        $candidates = $this->buildComparisonStats($candidates, $minimumGroupSize);
        $rankingRules = $this->extractRankingRules($type);
        $rankedCandidates = $this->rankCandidates($candidates, $rankingRules);

        // Qualification info
        $numberOfGroups = count($groupTeamCounts);
        $numAdvancing = $this->getNumAdvancingPerGroup($type);
        $knockoutSlots = $this->getKnockoutSlots($numAdvancing, $numberOfGroups);
        $totalFromPool = $numAdvancing * $numberOfGroups;
        $isPowerOfTwo = $totalFromPool > 0 && (($totalFromPool & ($totalFromPool - 1)) === 0);
        $additionalSlots = max(0, $knockoutSlots - $numberOfGroups);
        $applyTo = $this->extractApplyTo($rawConfig);

        // Gắn qualified flag dựa trên knockoutSlots (= 2^n gần nhất)
        $runnerUpCount = $rankedCandidates
            ->where('candidate_type', self::CANDIDATE_TYPE_RUNNER_UP)
            ->count();
        $thirdPlaceCount = $rankedCandidates
            ->where('candidate_type', self::CANDIDATE_TYPE_THIRD_PLACE)
            ->count();

        $rankedCandidates = $this->assignQualifiedStatus(
            $rankedCandidates,
            $knockoutSlots,
            $numberOfGroups,
            $applyTo
        );

        return [
            'enabled' => $evaluation['enabled'],
            'applied' => true,
            'comparison_rule' => [
                'minimum_group_size' => $minimumGroupSize,
                'description' => $this->buildRuleDescription($groupTeamCounts),
                'ranking_rules' => $rankingRules, // thứ tự ưu tiên thực tế được áp dụng
            ],
            'knockout_calculation' => [
                'num_advancing_per_group' => $numAdvancing,
                'number_of_groups' => $numberOfGroups,
                'total_from_pool_stage' => $totalFromPool,
                'is_power_of_two' => $isPowerOfTwo,
                'knockout_slots' => $knockoutSlots,
                'additional_slots' => $additionalSlots,
            ],
            'qualification' => [
                'number_of_groups' => $numberOfGroups,
                'knockout_slots' => $knockoutSlots,
                'additional_slots' => $additionalSlots,
                'runner_up_candidates' => $runnerUpCount,
                'third_place_candidates' => $thirdPlaceCount,
            ],
            'candidates' => $rankedCandidates->map(fn($c) => $this->formatCandidate($c))->values()->all(),
        ];
    }

    /**
     * Build candidate detail cho một team cụ thể — dùng cho API 2.
     *
     * @return array|null  null nếu team không phải candidate (không ở top 2/3 group)
     */
    public function buildTeamComparison(TournamentType $type, Team $team): ?array
    {
        $rawConfig = $this->extractCrossGroupRankingConfig($type);
        $evaluation = $this->crossGroupRankingService->evaluate($type, $rawConfig);

        if (!$evaluation['applied']) {
            return null;
        }

        $minimumGroupSize = (int) $evaluation['minimum_group_size'];
        $groups = $type->groups()->orderBy('id')->get();
        $candidates = $this->buildCandidates($groups, $minimumGroupSize);

        $target = $candidates->firstWhere('team_id', $team->id);
        if (!$target) {
            return null;
        }

        $stats = $this->buildCandidateStats($target, $minimumGroupSize);
        $matchList = $this->buildCandidateMatchList($target, $minimumGroupSize);

        return [
            'team' => [
                'id' => (string) $team->id,
                'name' => $team->name,
            ],
            'group' => [
                'id' => (string) $target['group']->id,
                'name' => $target['group']->name,
                'team_count' => $target['group_team_count'],
            ],
            'group_position' => $target['group_position'],
            'candidate_type' => $target['candidate_type'],
            'comparison' => [
                'minimum_group_size' => $minimumGroupSize,
                'original_matches' => $stats['original'],
                'counted_matches' => $stats['counted'],
                'excluded_matches' => $stats['excluded'],
                'wins' => $stats['wins'],
                'losses' => $stats['losses'],
                'win_rate' => $stats['win_rate'],
                'points_for' => $stats['points_for'],
                'points_against' => $stats['points_against'],
                'point_diff' => $stats['point_diff'],
                'average_point_difference' => $stats['average_point_difference'],
            ],
            'matches' => $matchList,
        ];
    }

    // ====================================================================
    // INTERNAL HELPERS
    // ====================================================================

    /**
     * Lấy config cross_group_ranking từ tournament type.
     */
    protected function extractCrossGroupRankingConfig(TournamentType $type): array
    {
        $config = $type->format_specific_config ?? [];
        $mainConfig = is_array($config) && isset($config[0]) ? $config[0] : (is_array($config) ? $config : []);
        return is_array($mainConfig['cross_group_ranking'] ?? null)
            ? $mainConfig['cross_group_ranking']
            : [];
    }

    /**
     * Lấy apply_to list từ config (đã normalize).
     */
    protected function extractApplyTo(array $rawConfig): array
    {
        $normalized = $this->crossGroupRankingService->normalizeConfig($rawConfig);
        return $normalized['apply_to'] ?? [];
    }

    /**
     * Lấy ranking rules từ format_specific_config[0].ranking.
     * Fallback về [1, 4, 5] giống TournamentTypeController::getRank.
     * Luôn thêm POINTS_WON (4) + HEAD_TO_HEAD (5) nếu thiếu (giống getRank).
     */
    protected function extractRankingRules(TournamentType $type): array
    {
        $config = $type->format_specific_config ?? [];
        $mainConfig = is_array($config) && isset($config[0]) ? $config[0] : (is_array($config) ? $config : []);

        $rules = collect($mainConfig['ranking'] ?? [1, 4, 5])
            ->map(fn($id) => (int) $id)
            ->toArray();

        if (!in_array(\App\Models\TournamentType::RANKING_POINTS_WON, $rules, true)) {
            $rules[] = \App\Models\TournamentType::RANKING_POINTS_WON;
        }
        if (!in_array(\App\Models\TournamentType::RANKING_HEAD_TO_HEAD, $rules, true)) {
            $rules[] = \App\Models\TournamentType::RANKING_HEAD_TO_HEAD;
        }

        return $rules;
    }

    /**
     * Lấy số đội đi tiếp / bảng từ format_specific_config[0].pool_stage.num_advancing_teams.
     */
    protected function getNumAdvancingPerGroup(TournamentType $type): int
    {
        $config = $type->format_specific_config ?? [];
        $mainConfig = is_array($config) && isset($config[0]) ? $config[0] : (is_array($config) ? $config : []);
        $pool = $mainConfig['pool_stage'] ?? [];
        return max(0, (int) ($pool['num_advancing_teams'] ?? 0));
    }

    /**
     * Tính số slot knockout theo 2^n gần nhất với numAdvancing × numGroups.
     *
     * Quy tắc:
     * - Vòng knockout yêu cầu số đội là 2^n (2, 4, 8, 16, 32).
     * - Nếu numAdvancing × numGroups KHÔNG phải 2^n, làm tròn về 2^n gần nhất:
     *   + Ưu tiên làm tròn xuống (gần numAdvancing × numGroups hơn)
     *   + Tie-break: ưu tiên làm tròn xuống
     * - Đảm bảo knockout_slots >= numberOfGroups (luôn có Nhất mỗi bảng đi tiếp).
     *
     * @param int $numAdvancing  Số đội đi tiếp / bảng (pool_stage.num_advancing_teams)
     * @param int $numberOfGroups Số bảng (pool_stage.number_competing_teams)
     */
    protected function getKnockoutSlots(int $numAdvancing, int $numberOfGroups): int
    {
        $total = max(0, $numAdvancing * $numberOfGroups);

        if ($total <= 0) {
            return 0;
        }

        // Edge case: tổng = 1 đội thì knockout không hợp lệ, ép về 2
        if ($total < 2) {
            return max(2, $numberOfGroups);
        }

        // Tính prev_power_of_2 và next_power_of_2
        $logVal = log($total, 2);
        $nextPower = (int) pow(2, (int) ceil($logVal));
        $prevPower = (int) pow(2, (int) floor($logVal));

        // Đảm bảo prevPower >= 1
        if ($prevPower < 1) {
            $prevPower = 1;
        }

        // Tính khoảng cách tới mỗi power
        $diffToPrev = $total - $prevPower;
        $diffToNext = $nextPower - $total;

        // Làm tròn xuống nếu gần hơn (hoặc bằng nhau → ưu tiên xuống)
        if ($diffToPrev <= $diffToNext && $prevPower >= 2) {
            $targetSlots = $prevPower;
        } else {
            $targetSlots = $nextPower;
        }

        // Đảm bảo >= numberOfGroups (luôn có Nhất mỗi bảng đi tiếp)
        return max((int) $numberOfGroups, $targetSlots);
    }

    /**
     * Build danh sách candidate từ các group.
     * Mỗi group: lấy top 2 (Nhì) + top 3 (Ba) theo actual standings.
     *
     * @param Collection<int,Group> $groups
     */
    protected function buildCandidates(Collection $groups, int $minimumGroupSize): Collection
    {
        $candidates = collect();

        foreach ($groups as $group) {
            $matches = $group->matches()->where('status', self::STATUS_COMPLETED)->get();
            $standings = TournamentService::calculateGroupStandings($matches);

            $groupTeamCount = $group->teams()->count();

            // position 2 = Nhì, position 3 = Ba
            foreach ([2, 3] as $position) {
                $standing = $standings->get($position - 1);
                if (!$standing) {
                    continue;
                }

                $candidates->push([
                    'team_id' => $standing['team']['id'],
                    'team_name' => $standing['team']['name'] ?? 'Unknown',
                    'group' => $group,
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'group_team_count' => $groupTeamCount,
                    'group_position' => $position,
                    'candidate_type' => $position === 2
                        ? self::CANDIDATE_TYPE_RUNNER_UP
                        : self::CANDIDATE_TYPE_THIRD_PLACE,
                ]);
            }
        }

        return $candidates;
    }

    /**
     * Build counted stats cho từng candidate.
     */
    protected function buildComparisonStats(Collection $candidates, int $minimumGroupSize): Collection
    {
        return $candidates->map(function (array $candidate) use ($minimumGroupSize) {
            $stats = $this->buildCandidateStats($candidate, $minimumGroupSize);
            // attach stats back to candidate
            return array_merge($candidate, $stats);
        });
    }

    /**
     * Tính counted stats cho 1 candidate dựa trên minimumGroupSize.
     *
     * @return array{original:int, counted:int, excluded:int, wins:int, losses:int, draws:int,
     *               win_rate:float, points_for:int, points_against:int, point_diff:int,
     *               average_point_difference:float, excluded_opponent_ids:array<int>}
     */
    protected function buildCandidateStats(array $candidate, int $minimumGroupSize): array
    {
        /** @var Group $group */
        $group = $candidate['group'];
        $teamId = $candidate['team_id'];
        $groupTeamCount = $candidate['group_team_count'];

        // Identify excluded opponents: bottom N teams theo final group standings
        $excludedOpponentIds = $this->getExcludedOpponentIds($group, $groupTeamCount, $minimumGroupSize);

        // Lấy toàn bộ match completed của team trong group này
        $matches = Matches::where('group_id', $group->id)
            ->where('status', self::STATUS_COMPLETED)
            ->where(function ($q) use ($teamId) {
                $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
            })
            ->with('results')
            ->get();

        $original = $matches->count();
        $countedMatches = $matches->reject(function (Matches $m) use ($teamId, $excludedOpponentIds) {
            $opponentId = $m->home_team_id === $teamId ? $m->away_team_id : $m->home_team_id;
            return in_array($opponentId, $excludedOpponentIds, true);
        });

        $counted = $countedMatches->count();
        $excluded = $original - $counted;

        // Tính stats chỉ trên counted matches
        $wins = 0;
        $losses = 0;
        $draws = 0;
        $pointsFor = 0;
        $pointsAgainst = 0;
        $setsWon = 0;
        $setsLost = 0;
        $points = 0; // điểm xếp hạng (Thắng=3, Hòa=1, Thua=0)

        foreach ($countedMatches as $match) {
            $isHome = $match->home_team_id === $teamId;
            $homeScore = (int) $match->results->where('team_id', $match->home_team_id)->sum('score');
            $awayScore = (int) $match->results->where('team_id', $match->away_team_id)->sum('score');

            // Số hiệp (set) thắng của từng đội
            $homeSetsWon = (int) $match->results->where('team_id', $match->home_team_id)->where('won_match', true)->count();
            $awaySetsWon = (int) $match->results->where('team_id', $match->away_team_id)->where('won_match', true)->count();

            if ($isHome) {
                $pointsFor += $homeScore;
                $pointsAgainst += $awayScore;
                $setsWon += $homeSetsWon;
                $setsLost += $awaySetsWon;
                if ($match->winner_id === $teamId) {
                    $wins++;
                    $points += 3;
                } elseif ($match->winner_id && $match->winner_id !== $teamId) {
                    $losses++;
                } else {
                    $draws++;
                    $points += 1;
                }
            } else {
                $pointsFor += $awayScore;
                $pointsAgainst += $homeScore;
                $setsWon += $awaySetsWon;
                $setsLost += $homeSetsWon;
                if ($match->winner_id === $teamId) {
                    $wins++;
                    $points += 3;
                } elseif ($match->winner_id && $match->winner_id !== $teamId) {
                    $losses++;
                } else {
                    $draws++;
                    $points += 1;
                }
            }
        }

        $pointDiff = $pointsFor - $pointsAgainst;
        $winRate = $counted > 0 ? round(($wins / $counted) * 100, 2) : 0.0;
        $avgPointDiff = $counted > 0 ? round($pointDiff / $counted, 2) : 0.0;

        return [
            'original' => $original,
            'counted' => $counted,
            'excluded' => $excluded,
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws,
            'points' => $points,
            'sets_won' => $setsWon,
            'sets_lost' => $setsLost,
            'sets_diff' => $setsWon - $setsLost,
            'win_rate' => $winRate,
            'points_for' => $pointsFor,
            'points_against' => $pointsAgainst,
            'point_diff' => $pointDiff,
            'average_point_difference' => $avgPointDiff,
            'excluded_opponent_ids' => $excludedOpponentIds,
        ];
    }

    /**
     * Xác định các đội bị loại khỏi comparison ở group này.
     *
     * Logic: với group có k đội và minimum m:
     * - excluded_count = k - m
     * - Loại top (k - excluded_count + 1) → k của standings, tức là rank từ (m + 1) đến k.
     *
     * Ví dụ:
     * - k = 5, m = 4 → loại rank 5 (1 đội cuối).
     * - k = 6, m = 4 → loại rank 5, 6 (2 đội cuối).
     */
    protected function getExcludedOpponentIds(Group $group, int $groupTeamCount, int $minimumGroupSize): array
    {
        if ($groupTeamCount <= $minimumGroupSize) {
            return [];
        }

        $matches = $group->matches()->where('status', self::STATUS_COMPLETED)->get();
        $standings = TournamentService::calculateGroupStandings($matches);

        $excludedCount = $groupTeamCount - $minimumGroupSize;
        $excludedIds = [];

        // Loại từ cuối BXH trở lên
        for ($i = 0; $i < $excludedCount; $i++) {
            $standing = $standings->get(($groupTeamCount - 1) - $i);
            if ($standing && isset($standing['team']['id'])) {
                $excludedIds[] = $standing['team']['id'];
            }
        }

        return $excludedIds;
    }

    /**
     * Rank candidates theo ranking rules đã được config trong format_specific_config[0].ranking.
     *
     * Hỗ trợ các rule constants (giống TournamentTypeController::getRank):
     *  - RANKING_WIN_DRAW_LOSE_POINTS (1): điểm xếp hạng (Thắng=3, Hòa=1, Thua=0)
     *  - RANKING_WIN_RATE (2): % thắng
     *  - RANKING_SETS_WON (3): số hiệp thắng
     *  - RANKING_POINTS_WON (4): hiệu số điểm
     *  - RANKING_HEAD_TO_HEAD (5): đối đầu trực tiếp
     *  - RANKING_RANDOM_DRAW (6): stable theo team_id
     *
     * pending_draw = true khi CÙNG candidate_type + cùng tất cả ranking keys đang xét.
     */
    protected function rankCandidates(Collection $candidates, array $rankingRules): Collection
    {
        // Tính head-to-head trước (nếu cần)
        $h2hMatrix = in_array(\App\Models\TournamentType::RANKING_HEAD_TO_HEAD, $rankingRules, true)
            ? $this->buildHeadToHeadMatrix($candidates)
            : [];

        $sorted = $candidates->sort(function ($a, $b) use ($rankingRules, $h2hMatrix) {
            // Ưu tiên runner_up trước third_place khi bằng nhau
            if ($a['candidate_type'] !== $b['candidate_type']) {
                return $a['candidate_type'] === self::CANDIDATE_TYPE_RUNNER_UP ? -1 : 1;
            }

            foreach ($rankingRules as $ruleId) {
                $cmp = match ($ruleId) {
                    \App\Models\TournamentType::RANKING_WIN_DRAW_LOSE_POINTS =>
                        $this->compareScalar($a, $b, 'points'),
                    \App\Models\TournamentType::RANKING_WIN_RATE =>
                        $this->compareScalar($a, $b, 'win_rate'),
                    \App\Models\TournamentType::RANKING_SETS_WON =>
                        $this->compareScalar($a, $b, 'sets_diff'),
                    \App\Models\TournamentType::RANKING_POINTS_WON =>
                        $this->compareScalar($a, $b, 'point_diff'),
                    \App\Models\TournamentType::RANKING_HEAD_TO_HEAD =>
                        $this->compareHeadToHead($a, $b, $h2hMatrix),
                    \App\Models\TournamentType::RANKING_RANDOM_DRAW =>
                        $a['team_id'] <=> $b['team_id'],
                    default => 0,
                };

                if ($cmp !== 0) {
                    return $cmp;
                }
            }

            // Fallback cuối: stable theo team_id
            return $a['team_id'] <=> $b['team_id'];
        })->values();

        // Gắn rank + pending_draw
        // pending_draw = true chỉ khi CẢ HAI đội cùng candidate_type
        // (cùng loại: runner_up hoặc third_place) và cùng tất cả ranking keys.
        $withRank = $sorted->map(function (array $c, int $idx) use ($sorted, $rankingRules) {
            $c['rank'] = $idx + 1;

            if ($idx > 0) {
                $prev = $sorted->get($idx - 1);
                $isSameType = $prev['candidate_type'] === $c['candidate_type'];
                $c['pending_draw'] = $isSameType && $this->isSameStats($prev, $c, $rankingRules);
            } else {
                $c['pending_draw'] = false;
            }

            return $c;
        });

        return $withRank;
    }

    /**
     * So sánh hai candidate theo một scalar ranking key (DESC).
     * Trả về -1/0/1 để dùng với usort.
     */
    protected function compareScalar(array $a, array $b, string $key): int
    {
        $av = (float) ($a[$key] ?? 0);
        $bv = (float) ($b[$key] ?? 0);
        if ($av === $bv) {
            return 0;
        }
        return $bv <=> $av; // DESC
    }

    /**
     * So sánh head-to-head giữa 2 đội (DESC ai thắng H2H).
     * Trả 0 nếu chưa gặp nhau hoặc tỷ số cân.
     */
    protected function compareHeadToHead(array $a, array $b, array $h2hMatrix): int
    {
        if (empty($h2hMatrix)) {
            return 0;
        }
        $keyA = $a['team_id'];
        $keyB = $b['team_id'];
        $pairKey = $keyA <= $keyB ? "{$keyA}|{$keyB}" : "{$keyB}|{$keyA}";
        $pair = $h2hMatrix[$pairKey] ?? null;

        if (!$pair) {
            return 0;
        }

        $aWins = ($pair['wins'][$keyA] ?? 0);
        $bWins = ($pair['wins'][$keyB] ?? 0);

        if ($aWins === $bWins) {
            return 0;
        }
        return $aWins < $bWins ? 1 : -1; // người thắng nhiều hơn xếp trên
    }

    /**
     * Kiểm tra 2 candidate có cùng stats trên các ranking keys không.
     */
    protected function isSameStats(array $a, array $b, array $rankingRules): bool
    {
        foreach ($rankingRules as $ruleId) {
            $key = match ($ruleId) {
                \App\Models\TournamentType::RANKING_WIN_DRAW_LOSE_POINTS => 'points',
                \App\Models\TournamentType::RANKING_WIN_RATE => 'win_rate',
                \App\Models\TournamentType::RANKING_SETS_WON => 'sets_diff',
                \App\Models\TournamentType::RANKING_POINTS_WON => 'point_diff',
                default => null,
            };
            if ($key === null) {
                continue;
            }
            if ((float) ($a[$key] ?? 0) !== (float) ($b[$key] ?? 0)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Build head-to-head matrix cho tất cả cặp candidate.
     * Key = "minId|maxId", value = { wins: [teamId => count], ... }
     */
    protected function buildHeadToHeadMatrix(Collection $candidates): array
    {
        $teamIds = $candidates->pluck('team_id')->all();
        if (count($teamIds) < 2) {
            return [];
        }

        $matches = Matches::where('status', self::STATUS_COMPLETED)
            ->whereIn('home_team_id', $teamIds)
            ->whereIn('away_team_id', $teamIds)
            ->get();

        $matrix = [];
        foreach ($matches as $match) {
            $homeId = (int) $match->home_team_id;
            $awayId = (int) $match->away_team_id;
            if (!in_array($homeId, $teamIds, true) || !in_array($awayId, $teamIds, true)) {
                continue;
            }
            $pairKey = $homeId <= $awayId ? "{$homeId}|{$awayId}" : "{$awayId}|{$homeId}";
            if (!isset($matrix[$pairKey])) {
                $matrix[$pairKey] = ['wins' => []];
            }
            if ($match->winner_id && in_array($match->winner_id, [$homeId, $awayId], true)) {
                $winner = (int) $match->winner_id;
                $matrix[$pairKey]['wins'][$winner] = ($matrix[$pairKey]['wins'][$winner] ?? 0) + 1;
            }
        }
        return $matrix;
    }

    /**
     * Gắn `status = qualified/not_qualified` cho từng candidate.
     *
     * Quy tắc đúng theo spec (sau khi fix):
     * - Số slot knockout = 2^n gần nhất với numAdvancing × numGroups (tính từ getKnockoutSlots).
     * - Số slot Nhất "mặc định" = numberOfGroups (mỗi bảng 1 Nhất).
     * - additionalSlots = knockoutSlots - numberOfGroups (số slot cần pick thêm).
     * - Khi additionalSlots == 0:
     *   + knockoutSlots = numberOfGroups → không cần pick thêm Nhì
     *   + Nhì KHÔNG qualified tự động (vì không có suất Nhì phụ)
     *   + Ba = not_applicable.
     * - Khi additionalSlots > 0:
     *   + Nhì: qualified mặc định (5 Nhì = 5 slot đầu tiên)
     *   + Nếu additionalSlots > numberOfGroups → pick thêm Ba (additionalSlots - numberOfGroups Ba tốt nhất)
     *   + Lưu ý: spec nói "Nhì trước, thiếu mới Ba" → nhưng trong case này Nhì đã chiếm hết numberOfGroups
     *     slot, phần "thiếu" phải lấy Ba.
     *
     * Status gắn in-place giữ nguyên order từ rankCandidates.
     *
     * @param int $knockoutSlots  Số slot knockout (= 2^n gần nhất)
     * @param int $numberOfGroups Số bảng (= số Nhất tự nhiên)
     */
    protected function assignQualifiedStatus(
        Collection $candidates,
        int $knockoutSlots,
        int $numberOfGroups,
        array $applyTo
    ): Collection {
        // Số slot cần pick thêm SAU Nhất mỗi bảng
        $additionalSlots = max(0, $knockoutSlots - $numberOfGroups);

        // Số Nhì tối đa được apply (mỗi bảng 1 Nhì)
        $runnerUpMaxApply = $numberOfGroups;

        // Sort candidates: runner_up trước third_place, theo rank
        $sorted = $candidates->sortBy([
            ['candidate_type', 'asc'],
            ['rank', 'asc'],
        ])->values();

        $qualifiedTeamIds = [];
        $needed = $additionalSlots;

        // Round 1: lấy Nhì (ưu tiên)
        // Nhưng chỉ lấy tối đa runnerUpMaxApply = numberOfGroups Nhì.
        // Phần dư (nếu additionalSlots > numberOfGroups) sẽ fill bằng Ba.
        if ($needed > 0 && in_array(self::CANDIDATE_TYPE_RUNNER_UP, $applyTo, true)) {
            $runnerUps = $sorted->where('candidate_type', self::CANDIDATE_TYPE_RUNNER_UP);
            $picked = 0;
            foreach ($runnerUps as $c) {
                if ($needed <= 0 || $picked >= $runnerUpMaxApply) {
                    break;
                }
                $qualifiedTeamIds[$c['team_id']] = true;
                $needed--;
                $picked++;
            }
        }

        // Round 2: nếu vẫn thiếu (additionalSlots > numberOfGroups), lấy Ba
        if ($needed > 0 && in_array(self::CANDIDATE_TYPE_THIRD_PLACE, $applyTo, true)) {
            $thirdPlaces = $sorted->where('candidate_type', self::CANDIDATE_TYPE_THIRD_PLACE);
            foreach ($thirdPlaces as $c) {
                if ($needed <= 0) {
                    break;
                }
                $qualifiedTeamIds[$c['team_id']] = true;
                $needed--;
            }
        }

        // Bước 2: gắn status
        return $candidates->map(function (array $candidate) use ($qualifiedTeamIds, $applyTo) {
            $type = $candidate['candidate_type'];

            if (!in_array($type, $applyTo, true)) {
                $candidate['status'] = 'not_applicable';
            } elseif (isset($qualifiedTeamIds[$candidate['team_id']])) {
                $candidate['status'] = 'qualified';
            } else {
                $candidate['status'] = 'not_qualified';
            }
            return $candidate;
        });
    }

    /**
     * Format candidate row thành JSON-friendly shape cho response.
     */
    protected function formatCandidate(array $c): array
    {
        return [
            'rank' => $c['rank'] ?? null,
            'team' => [
                'id' => (string) $c['team_id'],
                'name' => $c['team_name'] ?? 'Unknown',
            ],
            'group' => [
                'id' => (string) $c['group_id'],
                'name' => $c['group_name'] ?? '',
                'team_count' => (int) ($c['group_team_count'] ?? 0),
            ],
            'group_position' => $c['group_position'] ?? null,
            'candidate_type' => $c['candidate_type'] ?? null,
            'matches' => [
                'original' => (int) ($c['original'] ?? 0),
                'counted' => (int) ($c['counted'] ?? 0),
                'excluded' => (int) ($c['excluded'] ?? 0),
            ],
            'statistics' => [
                'wins' => (int) ($c['wins'] ?? 0),
                'losses' => (int) ($c['losses'] ?? 0),
                'draws' => (int) ($c['draws'] ?? 0),
                'points' => (int) ($c['points'] ?? 0), // điểm xếp hạng (Thắng=3, Hòa=1, Thua=0)
                'sets_won' => (int) ($c['sets_won'] ?? 0),
                'sets_lost' => (int) ($c['sets_lost'] ?? 0),
                'sets_diff' => (int) ($c['sets_diff'] ?? 0),
                'win_rate' => (float) ($c['win_rate'] ?? 0),
                'points_for' => (int) ($c['points_for'] ?? 0),
                'points_against' => (int) ($c['points_against'] ?? 0),
                'point_diff' => (int) ($c['point_diff'] ?? 0),
                'average_point_difference' => (float) ($c['average_point_difference'] ?? 0),
            ],
            'status' => $c['status'] ?? 'not_qualified',
            'pending_draw' => (bool) ($c['pending_draw'] ?? false),
            'has_excluded_matches' => (bool) (($c['excluded'] ?? 0) > 0),
        ];
    }

    /**
     * Build danh sách matches cho API 2 — bao gồm cả included và excluded.
     *
     * @return array<int,array>
     */
    protected function buildCandidateMatchList(array $candidate, int $minimumGroupSize): array
    {
        /** @var Group $group */
        $group = $candidate['group'];
        $teamId = $candidate['team_id'];
        $groupTeamCount = $candidate['group_team_count'];

        $excludedIds = $this->getExcludedOpponentIds($group, $groupTeamCount, $minimumGroupSize);

        // Standings cho opponent_group_position
        $matches = $group->matches()->where('status', self::STATUS_COMPLETED)->get();
        $standings = TournamentService::calculateGroupStandings($matches);
        $rankByTeamId = [];
        foreach ($standings as $standing) {
            if (isset($standing['team']['id'])) {
                $rankByTeamId[$standing['team']['id']] = $standing['rank'];
            }
        }

        $teamMatches = Matches::where('group_id', $group->id)
            ->where('status', self::STATUS_COMPLETED)
            ->where(function ($q) use ($teamId) {
                $q->where('home_team_id', $teamId)->orWhere('away_team_id', $teamId);
            })
            ->with(['homeTeam', 'awayTeam', 'results'])
            ->orderBy('id')
            ->get();

        $matchList = [];
        foreach ($teamMatches as $match) {
            $isHome = $match->home_team_id === $teamId;
            $opponent = $isHome ? $match->awayTeam : $match->homeTeam;
            $opponentId = $isHome ? $match->away_team_id : $match->home_team_id;

            $homeScore = (int) $match->results->where('team_id', $match->home_team_id)->sum('score');
            $awayScore = (int) $match->results->where('team_id', $match->away_team_id)->sum('score');

            $myScore = $isHome ? $homeScore : $awayScore;
            $opponentScore = $isHome ? $awayScore : $homeScore;

            // Determine result
            $result = 'draw';
            if ($match->winner_id === $teamId) {
                $result = 'win';
            } elseif ($match->winner_id && $match->winner_id !== $teamId) {
                $result = 'loss';
            }

            $included = !in_array($opponentId, $excludedIds, true);
            $reason = null;
            if (!$included) {
                $oppRank = $rankByTeamId[$opponentId] ?? null;
                $reason = $oppRank
                    ? "Đối thủ xếp hạng {$oppRank} trong bảng"
                    : 'Đối thủ xếp cuối bảng';
            }

            $matchList[] = [
                'id' => (string) $match->id,
                'opponent' => [
                    'id' => (string) ($opponent->id ?? $opponentId),
                    'name' => $opponent->name ?? 'Unknown',
                ],
                'opponent_group_position' => $rankByTeamId[$opponentId] ?? null,
                'score' => "{$myScore} - {$opponentScore}",
                'home_score' => $homeScore,
                'away_score' => $awayScore,
                'result' => $result,
                'included' => $included,
                'exclusion_reason' => $reason,
            ];
        }

        return $matchList;
    }

    /**
     * Build description string cho rule (dùng trong response FE nếu cần).
     */
    protected function buildRuleDescription(array $groupTeamCounts): string
    {
        $min = min(array_filter($groupTeamCounts));
        $sizes = implode(', ', $groupTeamCounts);
        return "Các bảng có số đội: {$sizes}. Bảng nhỏ nhất có {$min} đội. "
            . 'Các trận gặp đội xếp cuối bảng ở các bảng lớn hơn được loại để so sánh công bằng.';
    }

}