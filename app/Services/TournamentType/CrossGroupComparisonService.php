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
        $rankedCandidates = $this->rankCandidates($candidates);

        // Qualification info
        $numberOfGroups = count($groupTeamCounts);
        $knockoutSlots = $this->getKnockoutSlots($type, $numberOfGroups);
        $additionalSlots = max(0, $knockoutSlots - $numberOfGroups);
        $applyTo = $this->extractApplyTo($rawConfig);

        // Gắn qualified flag dựa trên top additionalSlots
        $runnerUpCount = $rankedCandidates
            ->where('candidate_type', self::CANDIDATE_TYPE_RUNNER_UP)
            ->count();
        $thirdPlaceCount = $rankedCandidates
            ->where('candidate_type', self::CANDIDATE_TYPE_THIRD_PLACE)
            ->count();

        $rankedCandidates = $this->assignQualifiedStatus(
            $rankedCandidates,
            $additionalSlots,
            $applyTo
        );

        return [
            'enabled' => $evaluation['enabled'],
            'applied' => true,
            'comparison_rule' => [
                'minimum_group_size' => $minimumGroupSize,
                'description' => $this->buildRuleDescription($groupTeamCounts),
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
     * Lấy tổng số suất vào vòng knockout.
     * Công thức: number_competing_teams * num_advancing_teams (từ pool_stage).
     */
    protected function getKnockoutSlots(TournamentType $type, int $numberOfGroups): int
    {
        $config = $type->format_specific_config ?? [];
        $mainConfig = is_array($config) && isset($config[0]) ? $config[0] : (is_array($config) ? $config : []);
        $pool = $mainConfig['pool_stage'] ?? [];

        $numAdvancing = (int) ($pool['num_advancing_teams'] ?? 0);
        return max(0, $numAdvancing * $numberOfGroups);
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

        foreach ($countedMatches as $match) {
            $isHome = $match->home_team_id === $teamId;
            $homeScore = (int) $match->results->where('team_id', $match->home_team_id)->sum('score');
            $awayScore = (int) $match->results->where('team_id', $match->away_team_id)->sum('score');

            if ($isHome) {
                $pointsFor += $homeScore;
                $pointsAgainst += $awayScore;
                if ($match->winner_id === $teamId) {
                    $wins++;
                } elseif ($match->winner_id && $match->winner_id !== $teamId) {
                    $losses++;
                } else {
                    $draws++;
                }
            } else {
                $pointsFor += $awayScore;
                $pointsAgainst += $homeScore;
                if ($match->winner_id === $teamId) {
                    $wins++;
                } elseif ($match->winner_id && $match->winner_id !== $teamId) {
                    $losses++;
                } else {
                    $draws++;
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
     * Rank candidates theo:
     * 1. win_rate DESC
     * 2. average_point_difference DESC
     * 3. points_for DESC
     * Nếu tất cả bằng → pending_draw = true (cho cặp đó).
     */
    protected function rankCandidates(Collection $candidates): Collection
    {
        // Mark pending_draw per pair sau khi sort
        $sorted = $candidates->sort(function ($a, $b) {
            // Ưu tiên runner_up trước third_place khi bằng nhau
            if ($a['candidate_type'] !== $b['candidate_type']) {
                return $a['candidate_type'] === self::CANDIDATE_TYPE_RUNNER_UP ? -1 : 1;
            }

            if ($a['win_rate'] !== $b['win_rate']) {
                return $b['win_rate'] <=> $a['win_rate'];
            }
            if ($a['average_point_difference'] !== $b['average_point_difference']) {
                return $b['average_point_difference'] <=> $a['average_point_difference'];
            }
            if ($a['points_for'] !== $b['points_for']) {
                return $b['points_for'] <=> $a['points_for'];
            }
            // Stable tie-break theo team_id
            return $a['team_id'] <=> $b['team_id'];
        })->values();

        // Gắn rank + pending_draw
        $withRank = $sorted->map(function (array $c, int $idx) use ($sorted) {
            $c['rank'] = $idx + 1;

            // So sánh với candidate trước đó: nếu cùng stats trên 3 tiêu chí → pending_draw
            if ($idx > 0) {
                $prev = $sorted->get($idx - 1);
                $isSameStats =
                    $prev['win_rate'] === $c['win_rate']
                    && $prev['average_point_difference'] === $c['average_point_difference']
                    && $prev['points_for'] === $c['points_for'];
                $c['pending_draw'] = $isSameStats;
            } else {
                $c['pending_draw'] = false;
            }

            return $c;
        });

        return $withRank;
    }

    /**
     * Gắn `status = qualified/not_qualified` cho từng candidate dựa trên top additionalSlots.
     *
     * Quy tắc:
     * - Xét runner_up trước, lấy tối đa additionalSlots từ danh sách runner_up.
     * - Nếu runner_up không đủ → xét tiếp third_place.
     *
     * KHÔNG reorder — giữ nguyên order từ rankCandidates() (đã được sort theo stats).
     * Status được gắn in-place bằng cách match team_id.
     */
    protected function assignQualifiedStatus(Collection $candidates, int $additionalSlots, array $applyTo): Collection
    {
        // Tạo qualification order riêng (theo candidate_type priority + rank)
        // → xác định những team_id nào qualified.
        $qualifiedTeamIds = [];

        $ordered = $candidates->sortBy([
            ['candidate_type', 'asc'], // runner_up < third_place alphabetically
            ['rank', 'asc'],
        ])->values();

        $needed = $additionalSlots;
        foreach ($ordered as $candidate) {
            if (!in_array($candidate['candidate_type'], $applyTo, true)) {
                continue; // không xét candidate này cho qualification
            }
            if ($needed > 0) {
                $qualifiedTeamIds[$candidate['team_id']] = true;
                $needed--;
            }
        }

        // Gắn status in-place giữ nguyên order từ rankCandidates
        return $candidates->map(function (array $candidate) use ($qualifiedTeamIds, $applyTo) {
            if (!in_array($candidate['candidate_type'], $applyTo, true)) {
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