<?php

namespace App\Services;

use Illuminate\Support\Collection;

class TournamentService
{
    /**
     * Tính bảng xếp hạng cho một group
     */
    public static function calculateGroupStandings($groupMatches): Collection
    {
        $standings = [];
        
        foreach ($groupMatches as $match) {
            if ($match->status !== 'completed') continue;

            $homeId = $match->home_team_id;
            $awayId = $match->away_team_id;

            // Khởi tạo standings cho home team
            if (!isset($standings[$homeId])) {
                $standings[$homeId] = [
                    'team' => self::formatTeam($match->homeTeam),
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

            // Khởi tạo standings cho away team
            if (!isset($standings[$awayId])) {
                $standings[$awayId] = [
                    'team' => self::formatTeam($match->awayTeam),
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

            // Cập nhật số liệu
            $standings[$homeId]['played']++;
            $standings[$awayId]['played']++;
            $standings[$homeId]['goals_for'] += $match->home_score ?? 0;
            $standings[$homeId]['goals_against'] += $match->away_score ?? 0;
            $standings[$awayId]['goals_for'] += $match->away_score ?? 0;
            $standings[$awayId]['goals_against'] += $match->home_score ?? 0;

            // Tính điểm
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

        // Sort standings
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
     * Format team data
     */
    public static function formatTeam($team): array
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
}