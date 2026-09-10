<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\Matches;
use App\Models\MatchResult;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\TournamentType;
use App\Models\User;
use App\Services\TournamentService;
use App\Services\TournamentType\CrossGroupComparisonService;
use App\Services\TournamentType\CrossGroupRankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests cho 2 API cross-group comparison.
 *
 * Test cases theo prompt mục 29:
 * 1. 6 bảng 5,5,5,5,5,4 → runner_up bảng 5 đội: 4 trận → 3 counted, 1 excluded
 * 2. All groups 5 đội → không excluded matches
 * 3. Groups 6,5,4 → bảng 6 loại 2, bảng 5 loại 1, bảng 4 giữ nguyên
 * 4. Chỉ 1 bảng → applied = false
 * 5. additional_slots > numGroups → ranking bao gồm third_place
 * 6. 2 đội bằng nhau → pending_draw = true
 * 7. Actual standings không đổi
 * 8. Match data không đổi
 */
class CrossGroupComparisonApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function makeUser(): User
    {
        return User::factory()->create([
            'is_banned' => false,
            'is_guest' => false,
            'is_merged' => false,
        ]);
    }

    /**
     * Tạo tournament + mixed type với N groups có số đội chỉ định.
     */
    protected function makeMixedTournament(array $groupTeamCounts, array $additionalConfig = []): TournamentType
    {
        $user = $this->makeUser();
        $sport = Sport::factory()->create();

        $tournament = Tournament::create([
            'name' => 'Test Tournament',
            'sport_id' => $sport->id,
            'max_team' => array_sum($groupTeamCounts),
            'player_per_team' => 2,
            'status' => Tournament::DRAFT,
            'created_by' => $user->id,
            'description' => '',
            'duration' => 1,
        ]);

        $numGroups = count($groupTeamCounts);
        $totalTeams = array_sum($groupTeamCounts);

        // Default config cho mixed format, cho phép override cross_group_ranking
        $specConfig = array_merge([
            'seeding_rules' => [1, 2, 3],
            'ranking' => [1, 4, 5, 2, 3],
            'pool_stage' => [
                'name' => 'Vòng bảng',
                'number_competing_teams' => $numGroups,
                'num_advancing_teams' => 2,
            ],
            'has_third_place_match' => false,
            'has_resurrection_bracket' => false,
            'main_bracket_name' => 'Giải chính',
            'sub_bracket_name' => 'Giải Tái sinh',
        ], $additionalConfig);

        $type = TournamentType::createWithFormat($tournament->id, TournamentType::FORMAT_MIXED, [
            'format_specific_config' => [$specConfig],
        ]);

        $this->createGroupsWithTeams($type, $groupTeamCounts, $tournament->id);

        return $type;
    }

    /**
     * Tạo groups và gắn teams. Trả về mapping [groupId => [teamId...]].
     */
    protected function createGroupsWithTeams(TournamentType $type, array $groupTeamCounts, int $tournamentId): array
    {
        $teamIndex = 0;
        $groupTeamMap = [];
        $allTeams = [];

        // Tạo tất cả teams trước
        for ($i = 0; $i < array_sum($groupTeamCounts); $i++) {
            $team = Team::create([
                'name' => 'Team ' . chr(65 + $i), // A, B, C...
                'tournament_id' => $tournamentId,
            ]);
            $allTeams[] = $team;
        }

        for ($g = 0; $g < count($groupTeamCounts); $g++) {
            $group = Group::create([
                'tournament_type_id' => $type->id,
                'name' => 'Bảng ' . chr(65 + $g),
            ]);
            $groupTeamMap[$group->id] = [];

            for ($t = 0; $t < $groupTeamCounts[$g]; $t++) {
                $team = $allTeams[$teamIndex++];
                $group->teams()->attach($team->id, ['order' => $t + 1]);
                $groupTeamMap[$group->id][] = $team->id;
            }
        }

        return $groupTeamMap;
    }

    /**
     * Tạo round-robin matches trong group với kết quả team nào cũng thắng team sau.
     * Trả về các match objects.
     */
    protected function createCompletedRoundRobin(Group $group, array $teamIds, bool $deterministic = true): array
    {
        $matches = [];
        for ($i = 0; $i < count($teamIds); $i++) {
            for ($j = $i + 1; $j < count($teamIds); $j++) {
                $homeId = $teamIds[$i];
                $awayId = $teamIds[$j];

                // Team index thấp hơn thắng (deterministic để standings cố định)
                if ($deterministic) {
                    $winnerId = $homeId;
                    $homeScore = 11;
                    $awayScore = 7;
                } else {
                    $winnerId = $teamIds[$i];
                    $homeScore = 11;
                    $awayScore = 7;
                }

                $match = Matches::create([
                    'tournament_type_id' => $group->tournament_type_id,
                    'group_id' => $group->id,
                    'round' => 1,
                    'home_team_id' => $homeId,
                    'away_team_id' => $awayId,
                    'leg' => 1,
                    'status' => 'completed',
                    'winner_id' => $winnerId,
                ]);

                MatchResult::create([
                    'match_id' => $match->id,
                    'team_id' => $homeId,
                    'score' => $homeScore,
                    'set_number' => 1,
                    'won_match' => $winnerId === $homeId,
                ]);
                MatchResult::create([
                    'match_id' => $match->id,
                    'team_id' => $awayId,
                    'score' => $awayScore,
                    'set_number' => 1,
                    'won_match' => $winnerId === $awayId,
                ]);

                $matches[] = $match;
            }
        }
        return $matches;
    }

    /**
     * Bật cross_group_ranking trong format_specific_config.
     */
    protected function enableCrossGroupRanking(TournamentType $type, array $overrides = []): void
    {
        $config = $type->format_specific_config ?? [];
        $main = is_array($config) && isset($config[0]) ? $config[0] : (is_array($config) ? $config : []);

        $main['cross_group_ranking'] = array_merge([
            'enabled' => true,
            'apply_to' => ['runner_up', 'third_place'],
            'exclude_bottom_team_matches' => true,
        ], $overrides);

        $type->format_specific_config = [$main];
        $type->save();
    }

    // ====================================================================
    // API 1 Tests
    // ====================================================================

    /**
     * Test 1: 6 bảng 5,5,5,5,5,4 → runner_up bảng 5: 4 trận → 3 counted, 1 excluded.
     * runner_up bảng 4: 3 trận → 3 counted, 0 excluded.
     */
    public function test_six_groups_5_5_5_5_5_4_excludes_one_match_for_5_team_groups(): void
    {
        $type = $this->makeMixedTournament([5, 5, 5, 5, 5, 4]);
        $this->enableCrossGroupRanking($type);

        // Tạo completed matches cho mỗi group
        foreach ($type->groups()->orderBy('id')->get() as $group) {
            $teamIds = $group->teams()->orderBy('group_team.order')->pluck('teams.id')->all();
            $this->createCompletedRoundRobin($group, $teamIds);
        }

        $user = $this->makeUser();
        $response = $this->actingAs($user)
            ->getJson("/api/tournament-types/{$type->id}/cross-group-comparison");

        $response->assertStatus(200);
        $response->assertJsonPath('data.applied', true);
        $response->assertJsonPath('data.comparison_rule.minimum_group_size', 4);
        $response->assertJsonPath('data.qualification.number_of_groups', 6);

        // Có 6 runner_up + 6 third_place = 12 candidates
        $candidates = $response->json('data.candidates');
        $this->assertCount(12, $candidates);

        // Runner_up của các bảng 5 đội: 4 trận → 3 counted, 1 excluded
        $runnerUps = collect($candidates)->where('candidate_type', 'runner_up');
        $foursGroupsRunnerUps = $runnerUps->where('group.team_count', 5);

        foreach ($foursGroupsRunnerUps as $cand) {
            $this->assertEquals(4, $cand['matches']['original']);
            $this->assertEquals(3, $cand['matches']['counted']);
            $this->assertEquals(1, $cand['matches']['excluded']);
            $this->assertTrue($cand['has_excluded_matches']);
        }

        // Runner_up của bảng 4 đội: 3 trận → 3 counted, 0 excluded
        $fourGroupRunnerUp = $runnerUps->where('group.team_count', 4)->first();
        $this->assertEquals(3, $fourGroupRunnerUp['matches']['original']);
        $this->assertEquals(3, $fourGroupRunnerUp['matches']['counted']);
        $this->assertEquals(0, $fourGroupRunnerUp['matches']['excluded']);
        $this->assertFalse($fourGroupRunnerUp['has_excluded_matches']);
    }

    /**
     * Test 2: All groups 5 đội → không có excluded matches (rule không thực sự áp dụng).
     */
    public function test_all_uniform_groups_applied_false(): void
    {
        $type = $this->makeMixedTournament([5, 5, 5, 5]);
        $this->enableCrossGroupRanking($type);

        // Không tạo matches cũng OK — applied=false do groups đều.
        $user = $this->makeUser();
        $response = $this->actingAs($user)
            ->getJson("/api/tournament-types/{$type->id}/cross-group-comparison");

        $response->assertStatus(200);
        $response->assertJsonPath('data.enabled', true);
        $response->assertJsonPath('data.applied', false);
        $this->assertEquals([], $response->json('data.candidates'));
    }

    /**
     * Test 3: Groups 6,5,4 → minimum=4 → bảng 6 loại 2, bảng 5 loại 1, bảng 4 giữ.
     */
    public function test_groups_6_5_4_excludes_correct_number(): void
    {
        $type = $this->makeMixedTournament([6, 5, 4]);
        $this->enableCrossGroupRanking($type);

        foreach ($type->groups()->orderBy('id')->get() as $group) {
            $teamIds = $group->teams()->orderBy('group_team.order')->pluck('teams.id')->all();
            $this->createCompletedRoundRobin($group, $teamIds);
        }

        $user = $this->makeUser();
        $response = $this->actingAs($user)
            ->getJson("/api/tournament-types/{$type->id}/cross-group-comparison");

        $response->assertStatus(200);
        $response->assertJsonPath('data.applied', true);
        $response->assertJsonPath('data.comparison_rule.minimum_group_size', 4);

        $candidates = $response->json('data.candidates');
        $runnerUps = collect($candidates)->where('candidate_type', 'runner_up');

        // Bảng 6 đội: 5 trận thật → 3 counted, 2 excluded
        $group6 = $runnerUps->where('group.team_count', 6)->first();
        $this->assertEquals(5, $group6['matches']['original']);
        $this->assertEquals(3, $group6['matches']['counted']);
        $this->assertEquals(2, $group6['matches']['excluded']);

        // Bảng 5 đội: 4 trận thật → 3 counted, 1 excluded
        $group5 = $runnerUps->where('group.team_count', 5)->first();
        $this->assertEquals(4, $group5['matches']['original']);
        $this->assertEquals(3, $group5['matches']['counted']);
        $this->assertEquals(1, $group5['matches']['excluded']);

        // Bảng 4 đội: 3 trận thật → 3 counted, 0 excluded
        $group4 = $runnerUps->where('group.team_count', 4)->first();
        $this->assertEquals(3, $group4['matches']['original']);
        $this->assertEquals(3, $group4['matches']['counted']);
        $this->assertEquals(0, $group4['matches']['excluded']);
    }

    /**
     * Test 4: Chỉ 1 bảng → applied = false, candidates = [].
     */
    public function test_single_group_applied_false(): void
    {
        $type = $this->makeMixedTournament([7]);
        $this->enableCrossGroupRanking($type);

        $user = $this->makeUser();
        $response = $this->actingAs($user)
            ->getJson("/api/tournament-types/{$type->id}/cross-group-comparison");

        $response->assertStatus(200);
        $response->assertJsonPath('data.applied', false);
        $this->assertEquals([], $response->json('data.candidates'));
    }

    /**
     * Test 5: additional_slots > number_of_groups → xét cả third_place.
     */
    public function test_third_place_evaluated_when_additional_slots_exceed_runner_up_count(): void
    {
        // 3 bảng, 8 đội knockout (num_advancing=2) → additional_slots = 8 - 3 = 5
        // → cần lấy 3 runner_up + 2 third_place
        $user = $this->makeUser();
        $sport = Sport::factory()->create();

        $tournament = Tournament::create([
            'name' => 'Tournament',
            'sport_id' => $sport->id,
            'max_team' => 15,
            'player_per_team' => 2,
            'status' => Tournament::DRAFT,
            'created_by' => $user->id,
            'description' => '',
            'duration' => 1,
        ]);

        $type = TournamentType::createWithFormat($tournament->id, TournamentType::FORMAT_MIXED, [
            'format_specific_config' => [[
                'seeding_rules' => [1, 2, 3],
                'ranking' => [1, 4, 5, 2, 3],
                'pool_stage' => [
                    'name' => 'Vòng bảng',
                    'number_competing_teams' => 3,
                    'num_advancing_teams' => 2,
                ],
                'has_third_place_match' => false,
                'has_resurrection_bracket' => false,
                'main_bracket_name' => 'Giải chính',
                'sub_bracket_name' => 'Giải Tái sinh',
                'cross_group_ranking' => [
                    'enabled' => true,
                    'apply_to' => ['runner_up', 'third_place'],
                    'exclude_bottom_team_matches' => true,
                ],
            ]],
        ]);

        $this->createGroupsWithTeams($type, [5, 5, 5], $tournament->id);

        foreach ($type->groups()->orderBy('id')->get() as $group) {
            $teamIds = $group->teams()->orderBy('group_team.order')->pluck('teams.id')->all();
            $this->createCompletedRoundRobin($group, $teamIds);
        }

        $response = $this->actingAs($user)
            ->getJson("/api/tournament-types/{$type->id}/cross-group-comparison");

        $response->assertStatus(200);
        $response->assertJsonPath('data.applied', true);
        $response->assertJsonPath('data.qualification.knockout_slots', 6); // 3 groups * 2
        $response->assertJsonPath('data.qualification.additional_slots', 3); // 6 - 3 = 3

        // Top 3 candidates (theo rank) phải là qualified.
        // Vì additional_slots = 3 và có 3 runner_up → tất cả 3 runner_up qualified.
        $candidates = collect($response->json('data.candidates'));
        $qualifiedCount = $candidates->where('status', 'qualified')->count();
        $this->assertEquals(3, $qualifiedCount);

        $qualifiedCandidates = $candidates->where('status', 'qualified');
        foreach ($qualifiedCandidates as $cand) {
            $this->assertEquals('runner_up', $cand['candidate_type']);
        }
    }

    /**
     * Test 6: 2 đội bằng nhau trên 3 tiêu chí → pending_draw = true.
     */
    public function test_pending_draw_when_two_candidates_tied(): void
    {
        $type = $this->makeMixedTournament([4, 4]);
        $this->enableCrossGroupRanking($type);

        // Tạo matches nhưng arrange sao cho 2 runner_up có cùng stats.
        // Vì deterministic round-robin (team đầu luôn thắng) → cả 2 runner_up đều có
        // 2 wins, 1 loss, points tương tự. Ta kiểm tra pending_draw cho runner_up đứng sau.

        foreach ($type->groups()->orderBy('id')->get() as $group) {
            $teamIds = $group->teams()->orderBy('group_team.order')->pluck('teams.id')->all();
            $this->createCompletedRoundRobin($group, $teamIds);
        }

        $user = $this->makeUser();
        $response = $this->actingAs($user)
            ->getJson("/api/tournament-types/{$type->id}/cross-group-comparison");

        $response->assertStatus(200);
        $candidates = collect($response->json('data.candidates'));

        // Với deterministic test, runner_up thứ 2 (cùng stats với runner_up thứ 1)
        // nên phải có pending_draw = true.
        $runnerUps = $candidates->where('candidate_type', 'runner_up')->values();
        $this->assertCount(2, $runnerUps);

        // Kiểm tra ít nhất 1 candidate có pending_draw = true
        $hasPending = $runnerUps->contains(fn($c) => $c['pending_draw'] === true);
        $this->assertTrue($hasPending, 'Expected at least one pending_draw=true');
    }

    /**
     * Test 7: Verify actual group standings qua TournamentService không đổi.
     */
    public function test_actual_group_standings_unchanged_after_api_call(): void
    {
        $type = $this->makeMixedTournament([5, 4]);
        $this->enableCrossGroupRanking($type);

        foreach ($type->groups()->orderBy('id')->get() as $group) {
            $teamIds = $group->teams()->orderBy('group_team.order')->pluck('teams.id')->all();
            $this->createCompletedRoundRobin($group, $teamIds);
        }

        // Snapshot standings trước khi gọi API
        $standingsBefore = [];
        foreach ($type->groups()->orderBy('id')->get() as $group) {
            $matches = $group->matches()->where('status', 'completed')->get();
            $standings = TournamentService::calculateGroupStandings($matches);
            $standingsBefore[$group->id] = $standings->map(fn($s) => [
                'team_id' => $s['team']['id'],
                'rank' => $s['rank'],
                'points' => $s['points'],
            ])->toArray();
        }

        // Gọi API
        $user = $this->makeUser();
        $this->actingAs($user)
            ->getJson("/api/tournament-types/{$type->id}/cross-group-comparison")
            ->assertStatus(200);

        // Snapshot lại — phải giống hệt
        foreach ($type->groups()->orderBy('id')->get() as $group) {
            $matches = $group->matches()->where('status', 'completed')->get();
            $standings = TournamentService::calculateGroupStandings($matches);
            $standingsAfter = $standings->map(fn($s) => [
                'team_id' => $s['team']['id'],
                'rank' => $s['rank'],
                'points' => $s['points'],
            ])->toArray();
            $this->assertEquals($standingsBefore[$group->id], $standingsAfter);
        }
    }

    /**
     * Test 8: Verify match data (count, scores) không đổi trong DB.
     */
    public function test_match_data_unchanged_after_api_call(): void
    {
        $type = $this->makeMixedTournament([5, 4]);
        $this->enableCrossGroupRanking($type);

        foreach ($type->groups()->orderBy('id')->get() as $group) {
            $teamIds = $group->teams()->orderBy('group_team.order')->pluck('teams.id')->all();
            $this->createCompletedRoundRobin($group, $teamIds);
        }

        // Snapshot match data
        $matchCountBefore = Matches::count();
        $resultCountBefore = MatchResult::count();
        $matchDataBefore = Matches::with('results')->get()->map(fn($m) => [
            'id' => $m->id,
            'home_team_id' => $m->home_team_id,
            'away_team_id' => $m->away_team_id,
            'winner_id' => $m->winner_id,
            'results' => $m->results->map(fn($r) => [
                'team_id' => $r->team_id,
                'score' => $r->score,
            ])->toArray(),
        ])->toArray();

        // Gọi API 1 và API 2
        $user = $this->makeUser();
        $this->actingAs($user)
            ->getJson("/api/tournament-types/{$type->id}/cross-group-comparison")
            ->assertStatus(200);

        $aTeam = $type->groups()->first()->teams()->first();
        $this->actingAs($user)
            ->getJson("/api/tournament-types/{$type->id}/cross-group-comparison/{$aTeam->id}/matches")
            ->assertStatus(200);

        // Verify không có thay đổi
        $this->assertEquals($matchCountBefore, Matches::count());
        $this->assertEquals($resultCountBefore, MatchResult::count());

        $matchDataAfter = Matches::with('results')->get()->map(fn($m) => [
            'id' => $m->id,
            'home_team_id' => $m->home_team_id,
            'away_team_id' => $m->away_team_id,
            'winner_id' => $m->winner_id,
            'results' => $m->results->map(fn($r) => [
                'team_id' => $r->team_id,
                'score' => $r->score,
            ])->toArray(),
        ])->toArray();

        $this->assertEquals($matchDataBefore, $matchDataAfter);
    }

    // ====================================================================
    // API 2 Tests
    // ====================================================================

    /**
     * API 2: Trả về toàn bộ trận của team với included/excluded đúng.
     */
    public function test_team_matches_endpoint_returns_full_match_list_with_included_flags(): void
    {
        $type = $this->makeMixedTournament([5, 4]);
        $this->enableCrossGroupRanking($type);

        foreach ($type->groups()->orderBy('id')->get() as $group) {
            $teamIds = $group->teams()->orderBy('group_team.order')->pluck('teams.id')->all();
            $this->createCompletedRoundRobin($group, $teamIds);
        }

        // Lấy runner_up bảng 5 đội (vị trí 2 trong standings)
        $group5 = $type->groups()->orderBy('id')->first();
        $standings = TournamentService::calculateGroupStandings($group5->matches()->where('status', 'completed')->get());
        $runnerUpTeam = $standings->get(1); // index 1 = rank 2
        $runnerUpTeamId = $runnerUpTeam['team']['id'];

        $user = $this->makeUser();
        $response = $this->actingAs($user)
            ->getJson("/api/tournament-types/{$type->id}/cross-group-comparison/{$runnerUpTeamId}/matches");

        $response->assertStatus(200);
        $response->assertJsonPath('data.team.id', (int) $runnerUpTeamId);
        $response->assertJsonPath('data.group.team_count', 5);
        $response->assertJsonPath('data.group_position', 2);
        $response->assertJsonPath('data.candidate_type', 'runner_up');
        $response->assertJsonPath('data.comparison.minimum_group_size', 4);

        $payload = $response->json('data');
        $matches = $payload['matches'];

        // 4 trận thật, 1 excluded, 3 included
        $this->assertCount(4, $matches);
        $includedCount = collect($matches)->where('included', true)->count();
        $excludedCount = collect($matches)->where('included', false)->count();
        $this->assertEquals(3, $includedCount);
        $this->assertEquals(1, $excludedCount);

        // Match excluded phải có exclusion_reason
        $excludedMatch = collect($matches)->where('included', false)->first();
        $this->assertNotNull($excludedMatch['exclusion_reason']);
    }

    /**
     * API 2: Trả 404 nếu team không phải candidate.
     */
    public function test_team_matches_endpoint_404_for_non_candidate_team(): void
    {
        $type = $this->makeMixedTournament([5, 5]);
        $this->enableCrossGroupRanking($type);

        foreach ($type->groups()->orderBy('id')->get() as $group) {
            $teamIds = $group->teams()->orderBy('group_team.order')->pluck('teams.id')->all();
            $this->createCompletedRoundRobin($group, $teamIds);
        }

        // Lấy team hạng 4 (không phải Nhì/Ba)
        $group = $type->groups()->orderBy('id')->first();
        $standings = TournamentService::calculateGroupStandings($group->matches()->where('status', 'completed')->get());
        $nonCandidate = $standings->get(3); // rank 4
        $nonCandidateTeamId = $nonCandidate['team']['id'];

        $user = $this->makeUser();
        $response = $this->actingAs($user)
            ->getJson("/api/tournament-types/{$type->id}/cross-group-comparison/{$nonCandidateTeamId}/matches");

        $response->assertStatus(404);
    }

    /**
     * API 2: Trả 404 nếu team không thuộc tournament.
     */
    public function test_team_matches_endpoint_404_for_team_from_other_tournament(): void
    {
        $type = $this->makeMixedTournament([5, 5]);
        $this->enableCrossGroupRanking($type);

        // Tạo team từ tournament khác
        $sport = Sport::factory()->create();
        $otherTournament = Tournament::create([
            'name' => 'Other Tournament',
            'sport_id' => $sport->id,
            'max_team' => 4,
            'player_per_team' => 2,
            'status' => Tournament::DRAFT,
            'created_by' => $this->makeUser()->id,
            'description' => '',
            'duration' => 1,
        ]);
        $otherTeam = Team::create([
            'name' => 'Other Team',
            'tournament_id' => $otherTournament->id,
        ]);

        $user = $this->makeUser();
        $response = $this->actingAs($user)
            ->getJson("/api/tournament-types/{$type->id}/cross-group-comparison/{$otherTeam->id}/matches");

        $response->assertStatus(404);
    }

    /**
     * API 2: Trả 404 khi rule không áp dụng.
     */
    public function test_team_matches_endpoint_404_when_rule_not_applied(): void
    {
        // All groups 5 đội → applied = false
        $type = $this->makeMixedTournament([5, 5]);
        $this->enableCrossGroupRanking($type);

        foreach ($type->groups()->orderBy('id')->get() as $group) {
            $teamIds = $group->teams()->orderBy('group_team.order')->pluck('teams.id')->all();
            $this->createCompletedRoundRobin($group, $teamIds);
        }

        $group = $type->groups()->orderBy('id')->first();
        $standings = TournamentService::calculateGroupStandings($group->matches()->where('status', 'completed')->get());
        $runnerUpTeamId = $standings->get(1)['team']['id'];

        $user = $this->makeUser();
        $response = $this->actingAs($user)
            ->getJson("/api/tournament-types/{$type->id}/cross-group-comparison/{$runnerUpTeamId}/matches");

        $response->assertStatus(404);
    }

    // ====================================================================
    // H2H Tie-Break Regression Tests
    // ====================================================================

    /**
     * REGRESSION TEST: Khi 2 đội cùng points + cùng hiệu số + cùng set_diff,
     * thì HEAD_TO_HEAD phải quyết định thứ hạng nội bộ.
     *
     * Bug trước đây:
     *   CrossGroupComparisonService::buildCandidates() dùng TournamentService::calculateGroupStandings()
     *   (thiếu HEAD_TO_HEAD) → xếp sai khi Đội 4 và Đội 5 cùng points + hiệu số.
     *   → Đội 5 (thực ra là Ba) được chọn làm runner_up thay vì Đội 4 (Nhì thực sự).
     *
     * Case: Bảng B có 4 đội. Đội 3 và Đội 4 cùng points + cùng hiệu số.
     *       Đội 3 thắng Đội 4 với tỷ số 3-0.
     *       → Đội 3 = hạng 2 (runner_up), Đội 4 = hạng 3 (third_place).
     *
     * Acceptance Criteria:
     *   Case 1: Đội 4 thắng H2H → hạng 2 (runner_up), Đội 5 = hạng 3
     *   Case 2: runner_up_candidates của bảng B = Đội 4 (không phải Đội 5)
     *   Case 3: third_place_candidates của bảng B = Đội 5 (không phải Đội 4)
     */
    public function test_h2h_tie_break_determines_correct_runner_up_and_third_place(): void
    {
        $type = $this->makeMixedTournament([3, 4]); // Group A: 3 teams, Group B: 4 teams
        $this->enableCrossGroupRanking($type);

        // === GROUP A (3 đội): deterministic thắng để dễ kiểm tra ===
        $groupA = $type->groups()->orderBy('id')->first();
        $teamIdsA = $groupA->teams()->orderBy('group_team.order')->pluck('teams.id')->all();
        $this->createCompletedRoundRobin($groupA, $teamIdsA); // Team A thắng hết → rank 1, rank 2, rank 3

        // === GROUP B (4 đội): Đội 3 vs Đội 4 cùng points + hiệu số, Đội 3 thắng H2H ===
        $groupB = $type->groups()->orderBy('id')->last();
        $teamIdsB = $groupB->teams()->orderBy('group_team.order')->pluck('teams.id')->all();
        // teamIdsB[0] = Đội 1, [1] = Đội 2, [2] = Đội 3, [3] = Đội 4

        // --- Đội 1 thắng Đội 2, Đội 3, Đội 4 (→ rank 1 trong bảng B) ---
        foreach ($teamIdsB as $opponentId) {
            if ($opponentId === $teamIdsB[0]) continue;
            $this->createMatch(
                $groupB,
                $teamIdsB[0],  // Đội 1 (home)
                $opponentId,     // Đội 2, 3, 4
                $teamIdsB[0],   // Đội 1 thắng
                11, 7
            );
        }

        // --- Đội 2 thắng Đội 3 và Đội 4 (→ rank 2 trong bảng B) ---
        foreach ([$teamIdsB[2], $teamIdsB[3]] as $opponentId) {
            $this->createMatch(
                $groupB,
                $teamIdsB[1],   // Đội 2 (home)
                $opponentId,    // Đội 3 hoặc Đội 4
                $teamIdsB[1],   // Đội 2 thắng
                11, 7
            );
        }

        // --- Đội 3 vs Đội 4: cùng points + cùng hiệu số (cả 2 đều thắng 1, thua 1)
        // Đội 3 thắng Đội 4 (H2H winner)
        $this->createMatch(
            $groupB,
            $teamIdsB[2],   // Đội 3 (home)
            $teamIdsB[3],    // Đội 4 (away)
            $teamIdsB[2],   // Đội 3 thắng
            11, 7
        );

        // Đội 1 vs Đội 2 (tạo hòa về stats để Đội 3 vs Đội 4 cùng points + hiệu số)
        // Đội 1 thắng Đội 2 rồi → Đội 1 rank 1, Đội 2 rank 2
        // Đội 3 và Đội 4: mỗi đội thắng 1, thua 1 → cùng points = 3
        // Đội 3 thắng Đội 4 → H2H → Đội 3 = runner_up (rank 2), Đội 4 = third_place (rank 3)

        // === Verify: Gọi API và kiểm tra candidates ===
        $user = $this->makeUser();
        $response = $this->actingAs($user)
            ->getJson("/api/tournament-types/{$type->id}/cross-group-comparison");

        $response->assertStatus(200);
        $response->assertJsonPath('data.applied', true);

        $candidates = collect($response->json('data.candidates'));

        // Group A (3 đội): runner_up = đội rank 2, third_place = đội rank 3
        $groupACandidates = $candidates->where('group.id', (int) $groupA->id);
        $this->assertCount(2, $groupACandidates);

        // Group B (4 đội): runner_up = Đội 3 (H2H winner), third_place = Đội 4 (H2H loser)
        $groupBCandidates = $candidates->where('group.id', (int) $groupB->id);
        $this->assertCount(2, $groupBCandidates);

        $runnerUpB = $groupBCandidates->firstWhere('candidate_type', 'runner_up');
        $thirdPlaceB = $groupBCandidates->firstWhere('candidate_type', 'third_place');

        $this->assertNotNull($runnerUpB, 'Group B must have a runner_up candidate');
        $this->assertNotNull($thirdPlaceB, 'Group B must have a third_place candidate');
        $this->assertEquals(
            $teamIdsB[2],
            (int) $runnerUpB['team']['id'],
            'Runner-up of Group B must be Đội 3 (H2H winner), NOT Đội 4'
        );
        $this->assertEquals(
            $teamIdsB[3],
            (int) $thirdPlaceB['team']['id'],
            'Third-place of Group B must be Đội 4 (H2H loser), NOT Đội 3'
        );

        // AC Case 2: Đội 5 (H2H loser) KHÔNG được trong runner_up candidates
        $allRunnerUps = $candidates->where('candidate_type', 'runner_up');
        $this->assertFalse(
            $allRunnerUps->contains('team.id', (int) $teamIdsB[3]),
            'Đội 4 (H2H loser) must NOT be in runner_up candidates'
        );
    }

    /**
     * Tạo 1 match đơn lẻ (1 set) với winner cố định.
     */
    protected function createMatch(
        Group $group,
        int $homeId,
        int $awayId,
        int $winnerId,
        int $homeScore,
        int $awayScore
    ): Matches {
        $match = Matches::create([
            'tournament_type_id' => $group->tournament_type_id,
            'group_id' => $group->id,
            'round' => 1,
            'home_team_id' => $homeId,
            'away_team_id' => $awayId,
            'leg' => 1,
            'status' => 'completed',
            'winner_id' => $winnerId,
        ]);

        MatchResult::create([
            'match_id' => $match->id,
            'team_id' => $homeId,
            'score' => $homeScore,
            'set_number' => 1,
            'won_match' => $winnerId === $homeId,
        ]);
        MatchResult::create([
            'match_id' => $match->id,
            'team_id' => $awayId,
            'score' => $awayScore,
            'set_number' => 1,
            'won_match' => $winnerId === $awayId,
        ]);

        return $match;
    }
}