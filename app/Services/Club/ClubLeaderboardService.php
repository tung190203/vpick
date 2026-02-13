<?php

namespace App\Services\Club;

use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use App\Models\Matches;
use App\Models\MiniMatch;
use App\Models\VnduprHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClubLeaderboardService
{
    /**
     * Tính rank của club dựa trên tổng điểm members trong tháng
     */
    public function calculateClubRank(Club $club, ?int $month = null, ?int $year = null): ?int
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $allClubs = Club::where('status', \App\Enums\ClubStatus::Active)
            ->with(['joinedMembers.user.sports.scores'])
            ->get();

        $clubScores = $allClubs->map(function ($clubItem) use ($startDate, $endDate) {
            $members = $clubItem->joinedMembers;

            if ($members->isEmpty()) {
                return [
                    'club_id' => $clubItem->id,
                    'total_score' => 0,
                ];
            }

            $memberIds = $members->pluck('user_id')->filter()->unique();
            $histories = VnduprHistory::whereIn('user_id', $memberIds)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'asc')
                ->get()
                ->groupBy('user_id');

            $totalScore = 0;
            foreach ($members as $member) {
                $userId = $member->user_id;
                $userHistories = $histories->get($userId, collect());

                if ($userHistories->isNotEmpty()) {
                    $totalScore += $userHistories->last()->score_after;
                } else {
                    $vnduprScore = $member->user?->sports->flatMap(fn($sport) => $sport->scores)
                        ->where('score_type', 'vndupr_score')
                        ->sortByDesc('created_at')
                        ->first();
                    $totalScore += $vnduprScore ? $vnduprScore->score_value : 0;
                }
            }

            return [
                'club_id' => $clubItem->id,
                'total_score' => $totalScore,
            ];
        });

        $sortedClubs = $clubScores->sortByDesc('total_score')->values();

        $rank = null;
        foreach ($sortedClubs as $index => $item) {
            if ($item['club_id'] === $club->id) {
                $rank = $index + 1;
                break;
            }
        }

        return $rank;
    }

    public function getMonthlyLeaderboard(Club $club, int $month, int $year): Collection
    {
        $members = $club->joinedMembers()->with(['user.sports.scores'])->get();

        if ($members->isEmpty()) {
            return collect();
        }

        $memberIds = $members->pluck('user_id')->filter()->unique();

        // Lấy toàn bộ lịch sử (không lọc theo tháng) cho monthly_stats
        $histories = $this->getMemberHistories($memberIds);

        $matchIds = $histories->flatten()->pluck('match_id')->filter()->unique();
        $miniMatchIds = $histories->flatten()->pluck('mini_match_id')->filter()->unique();

        // Get matches and mini matches data
        $matches = $this->getMatchesData($matchIds);
        $miniMatches = $this->getMiniMatchesData($miniMatchIds);

        // Build leaderboard data
        $leaderboardData = $members->map(function ($member) use ($histories, $matches, $miniMatches) {
            return $this->calculateMemberStats($member, $histories, $matches, $miniMatches);
        });

        // Sort by vndupr_score descending and add rank attribute
        return $leaderboardData->sortByDesc('vndupr_score')->values()->map(function ($item, $index) {
            $item['rank'] = $index + 1;
            return $item;
        });
    }

    /**
     * Lấy toàn bộ lịch sử vndupr của members (không lọc theo tháng).
     */
    private function getMemberHistories(Collection $memberIds): Collection
    {
        return VnduprHistory::whereIn('user_id', $memberIds)
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('user_id');
    }

    private function getMatchesData(Collection $matchIds): Collection
    {
        return Matches::with(['homeTeam.members', 'awayTeam.members'])
            ->whereIn('id', $matchIds)
            ->get()
            ->keyBy('id');
    }

    private function getMiniMatchesData(Collection $miniMatchIds): array
    {
        $miniMatches = MiniMatch::whereIn('id', $miniMatchIds)
            ->get()
            ->keyBy('id');

        $miniTeamMembersByTeam = collect();
        if ($miniMatches->isNotEmpty()) {
            $miniTeamIds = $miniMatches->pluck('team1_id')
                ->merge($miniMatches->pluck('team2_id'))
                ->filter()
                ->unique();

            $miniTeamMembersByTeam = DB::table('mini_team_members')
                ->whereIn('mini_team_id', $miniTeamIds)
                ->get()
                ->groupBy('mini_team_id')
                ->map(fn($rows) => $rows->pluck('user_id')->all());
        }

        return [
            'miniMatches' => $miniMatches,
            'miniTeamMembersByTeam' => $miniTeamMembersByTeam,
        ];
    }

    private function calculateMemberStats(
        ClubMember $member,
        Collection $histories,
        Collection $matches,
        array $miniMatchesData
    ): array {
        $userId = $member->user_id;
        $userHistories = $histories->get($userId, collect());
        $miniMatches = $miniMatchesData['miniMatches'];
        $miniTeamMembersByTeam = $miniMatchesData['miniTeamMembersByTeam'];

        $finalScore = 0;
        if ($userHistories->isNotEmpty()) {
            $finalScore = $userHistories->last()->score_after;
        } else {
            $vnduprScore = $member->user?->sports->flatMap(fn($sport) => $sport->scores)
                ->where('score_type', 'vndupr_score')
                ->sortByDesc('created_at')
                ->first();
            $finalScore = $vnduprScore ? $vnduprScore->score_value : 0;
        }

        $matchesPlayed = $userHistories->count();
        $wins = 0;
        $losses = 0;
        $scoreChange = 0;

        if ($matchesPlayed > 0) {
            $scoreChange = $userHistories->last()->score_after - $userHistories->first()->score_before;

            foreach ($userHistories as $history) {
                $isWin = false;

                if ($history->match_id && $matches->has($history->match_id)) {
                    $match = $matches->get($history->match_id);
                    $homeUserIds = $match->homeTeam->members->pluck('id')->all();
                    $awayUserIds = $match->awayTeam->members->pluck('id')->all();

                    $isWin = (
                        ($match->winner_id == $match->home_team_id && in_array($userId, $homeUserIds)) ||
                        ($match->winner_id == $match->away_team_id && in_array($userId, $awayUserIds))
                    );
                } elseif ($history->mini_match_id && $miniMatches->has($history->mini_match_id)) {
                    $mini = $miniMatches->get($history->mini_match_id);
                    $team1Members = $miniTeamMembersByTeam[$mini->team1_id] ?? [];
                    $team2Members = $miniTeamMembersByTeam[$mini->team2_id] ?? [];

                    $isWin = (
                        (in_array($userId, $team1Members) && $mini->team_win_id == $mini->team1_id) ||
                        (in_array($userId, $team2Members) && $mini->team_win_id == $mini->team2_id)
                    );
                }

                if ($isWin) {
                    $wins++;
                } else {
                    $losses++;
                }
            }
        }

        $winRate = $matchesPlayed > 0 ? round(($wins / $matchesPlayed) * 100, 2) : 0;

        return [
            'member_id' => $member->id,
            'user_id' => $userId,
            'user' => $member->user,
            'vndupr_score' => round($finalScore, 3),
            'monthly_stats' => [
                'matches_played' => $matchesPlayed,
                'wins' => $wins,
                'losses' => $losses,
                'win_rate' => $winRate,
                'score_change' => round($scoreChange, 3),
            ],
        ];
    }
}
