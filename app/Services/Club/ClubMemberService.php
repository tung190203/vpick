<?php

namespace App\Services\Club;

use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use App\Models\Matches;
use App\Models\MiniMatch;
use App\Models\VnduprHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClubMemberService
{
    public function enrichMembersWithRanking(Collection $members): Collection
    {
        $members = $members->map(function ($member) {
            $user = $member->user;
            $score = 0;

            if ($user && $user->relationLoaded('sports')) {
                // Get VNDUPR score from user's sports
                foreach ($user->sports ?? [] as $us) {
                    $vndupr = $us->relationLoaded('scores')
                        ? $us->scores->where('score_type', 'vndupr_score')->sortByDesc('created_at')->first()
                        : null;
                    if ($vndupr) {
                        $score = (float) $vndupr->score_value;
                        break;
                    }
                }

                // Calculate win_rate and performance for each sport
                foreach ($user->sports ?? [] as $userSport) {
                    $stats = $this->calculateWinRateAndPerformance($user->id, $userSport->sport_id);
                    $userSport->setAttribute('win_rate', $stats['win_rate']);
                    $userSport->setAttribute('performance', $stats['performance']);
                }
            }

            $member->user?->setAttribute('club_score', $score);
            return $member;
        })->sortByDesc(fn ($m) => $m->user?->club_score ?? 0)->values();

        // Add rank_in_club attribute
        $members->each(fn ($member, $index) => $member->setAttribute('rank_in_club', $index + 1));

        return $members;
    }

    public function calculateWinRateAndPerformance(int $userId, int $sportId): array
    {
        $histories = VnduprHistory::where('user_id', $userId)
            ->latest()
            ->take(10)
            ->get();

        // Get unique histories by match/mini_match
        $uniqueHistories = collect();
        $seen = [];
        foreach ($histories as $h) {
            $key = $h->match_id ? 'match_' . $h->match_id : 'mini_' . $h->mini_match_id;
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueHistories->push($h);
            }
        }

        $totalMatches = $uniqueHistories->count();
        $wins = 0;
        $totalPoint = 0;

        if ($totalMatches > 0) {
            $matchIds = $uniqueHistories->pluck('match_id')->filter()->unique()->values()->all();
            $miniIds  = $uniqueHistories->pluck('mini_match_id')->filter()->unique()->values()->all();

            $matches = Matches::whereIn('id', $matchIds)->get()->keyBy('id');
            $minis = MiniMatch::withFullRelations()->whereIn('id', $miniIds)->get()->keyBy('id');

            // Get team members for regular matches
            $teamIds = $matches->pluck('winner_id')->filter()->unique()->values()->all();
            $teamMembersByTeam = collect();
            if (!empty($teamIds)) {
                $members = DB::table('team_members')
                    ->whereIn('team_id', $teamIds)
                    ->get();
                $teamMembersByTeam = $members->groupBy('team_id')
                    ->map(fn($rows) => $rows->pluck('user_id')->flip());
            }

            // Get team members for mini matches
            $miniTeamMembersByTeam = DB::table('mini_team_members')
                ->whereIn(
                    'mini_team_id',
                    $minis->pluck('team1_id')
                        ->merge($minis->pluck('team2_id'))
                        ->filter()
                        ->unique()
                )
                ->get()
                ->groupBy('mini_team_id')
                ->map(fn($rows) => $rows->pluck('user_id')->flip());

            foreach ($uniqueHistories->values() as $index => $history) {
                $isWin = false;

                if ($history->match_id) {
                    $match = $matches->get($history->match_id);
                    if ($match && $match->winner_id) {
                        $teamMembers = $teamMembersByTeam->get($match->winner_id);
                        $isWin = $teamMembers ? $teamMembers->has($userId) : false;
                    }
                } elseif ($history->mini_match_id) {
                    $mini = $minis->get($history->mini_match_id);
                    if ($mini && $mini->team_win_id) {
                        $winningTeamMembers = $miniTeamMembersByTeam->get($mini->team_win_id);
                        $isWin = $winningTeamMembers ? $winningTeamMembers->has($userId) : false;
                    }
                }

                if ($isWin) {
                    $wins++;
                    $coef = $index < 3 ? 1.5 : 1.0;
                    $totalPoint += 10 * $coef;
                }
            }
        }

        // Calculate win_rate
        $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 2) : 0;

        // Calculate performance
        $maxPoint = 0;
        for ($i = 0; $i < $totalMatches; $i++) {
            $maxPoint += $i < 3 ? 15 : 10;
        }
        $performance = $maxPoint > 0 ? round(($totalPoint / $maxPoint) * 100, 2) : 0;

        return [
            'win_rate' => $winRate,
            'performance' => $performance,
        ];
    }

    public function countActiveAdmins(Club $club): int
    {
        return $club->countActiveAdmins();
    }

    public function hasAtLeastOneAdminAfterRemoving(Club $club, int $memberIdToRemove): bool
    {
        return $club->hasAtLeastOneAdminAfterRemoving($memberIdToRemove);
    }
}
