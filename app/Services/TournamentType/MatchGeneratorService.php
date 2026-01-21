<?php

namespace App\Services\TournamentType;

use App\Models\Matches;
use App\Models\TournamentType;
use Illuminate\Support\Collection;

/**
 * Service chuyên xử lý logic tạo matches cho các dạng tournament
 */
class MatchGeneratorService
{
    /**
     * Tạo matches cho Round Robin format
     */
    public function generateRoundRobin(
        TournamentType $type,
        Collection $teams,
        int $numLegs,
        ?int $groupId = null
    ): int {
        $teamCount = $teams->count();
        if ($teamCount < 2) {
            return 0;
        }

        $scheduleTeams = $teams->pluck('id')->toArray();
        $isOdd = $teamCount % 2 !== 0;

        if ($isOdd) {
            $scheduleTeams[] = 'BYE';
            $teamCount++;
        }

        $totalRounds = $teamCount - 1;
        $matches = [];
        $matchNumber = 0;

        for ($leg = 1; $leg <= $numLegs; $leg++) {
            $currentSchedule = $scheduleTeams; // Reset schedule mỗi leg

            for ($round = 1; $round <= $totalRounds; $round++) {
                $halfSize = $teamCount / 2;
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

                    $matchData = [
                        'name_of_match' => "Trận đấu số {$matchNumber}",
                        'home_team_id' => $finalHomeId,
                        'away_team_id' => $finalAwayId,
                        'tournament_type_id' => $type->id,
                        'leg' => $leg,
                        'round' => $round,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if ($groupId) {
                        $matchData['group_id'] = $groupId;
                    }

                    $matches[] = $matchData;
                }

                // Rotate schedule (Circle Method)
                $firstTeam = array_shift($currentSchedule);
                $lastTeam = array_pop($currentSchedule);
                array_unshift($currentSchedule, $firstTeam, $lastTeam);
            }
        }

        // Insert tất cả một lần để tối ưu performance
        if (!empty($matches)) {
            Matches::insert($matches);
        }

        return $matchNumber;
    }

    /**
     * Tạo bye match cho team đơn
     */
    public function createByeMatch(
        TournamentType $type,
        int $teamId,
        int $round,
        int $leg,
        int $matchNumber,
        ?int $groupId = null
    ): Matches {
        $matchData = [
            'tournament_type_id' => $type->id,
            'home_team_id' => $teamId,
            'away_team_id' => null,
            'round' => $round,
            'leg' => $leg,
            'is_bye' => true,
            'status' => 'pending',
            'name_of_match' => "Trận đấu số {$matchNumber}",
        ];

        if ($groupId) {
            $matchData['group_id'] = $groupId;
        }

        return $type->matches()->create($matchData);
    }

    /**
     * Tính tổng số match cần tạo cho Round Robin
     */
    public function calculateRoundRobinMatchCount(int $teamCount, int $numLegs): int
    {
        if ($teamCount < 2) {
            return 0;
        }

        $isOdd = $teamCount % 2 !== 0;
        $adjustedCount = $isOdd ? $teamCount + 1 : $teamCount;
        $roundsPerLeg = $adjustedCount - 1;
        $matchesPerRound = $adjustedCount / 2;
        $totalMatches = $roundsPerLeg * $matchesPerRound * $numLegs;

        return (int) $totalMatches;
    }

    /**
     * Helper: Lấy team ID từ placeholder object
     */
    public function getTeamId($placeholder): ?int
    {
        if (!$placeholder) {
            return null;
        }

        if (is_object($placeholder) && isset($placeholder->team_id)) {
            return $placeholder->team_id;
        }

        return null;
    }
}
