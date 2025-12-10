<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class UserSportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $types = ['personal_score', 'dupr_score', 'vndupr_score'];
        $scores = $this->relationLoaded('scores') ? $this->scores : collect();
        
        $formattedScores = [];
        foreach ($types as $type) {
            $latestScore = $scores->where('score_type', $type)->sortByDesc('created_at')->first();
            $scoreValue = $latestScore ? $latestScore->score_value : 0;
            $formattedScores[$type] = number_format($scoreValue, 3);
        }

        // Tính total_matches
        $userId = $this->user_id;
        $sportId = $this->sport_id;

        // A. Matches
        $matchCount = DB::table('vndupr_history')
            ->join('matches', 'vndupr_history.match_id', '=', 'matches.id')
            ->join('tournament_types', 'matches.tournament_type_id', '=', 'tournament_types.id')
            ->join('tournaments', 'tournament_types.tournament_id', '=', 'tournaments.id')
            ->where('vndupr_history.user_id', $userId)
            ->where('tournaments.sport_id', $sportId)
            ->count();

        // B. Mini matches
        $miniMatchCount = DB::table('vndupr_history')
            ->join('mini_matches', 'vndupr_history.mini_match_id', '=', 'mini_matches.id')
            ->join('mini_tournaments', 'mini_matches.mini_tournament_id', '=', 'mini_tournaments.id')
            ->where('vndupr_history.user_id', $userId)
            ->where('mini_tournaments.sport_id', $sportId)
            ->count();

        // Lấy danh sách match_id từ vndupr_history
        $matchIds = DB::table('vndupr_history')
            ->where('user_id', $userId)
            ->whereNotNull('match_id')
            ->pluck('match_id')
            ->toArray();

        $tournamentsCount = 0;
        if (!empty($matchIds)) {
            $tournamentsCount = DB::table('matches as m')
                ->join('tournament_types as tt', 'm.tournament_type_id', '=', 'tt.id')
                ->join('tournaments as t', 'tt.tournament_id', '=', 't.id')
                ->whereIn('m.id', $matchIds)
                ->where('t.sport_id', $sportId)
                ->distinct()
                ->count('t.id');
        }

        $totalMatches = $matchCount + $miniMatchCount;

        return [
            'sport_id'   => $this->sport_id,
            'sport_icon' => $this->relationLoaded('sport') ? optional($this->sport)->icon : null,
            'sport_name' => $this->relationLoaded('sport') ? optional($this->sport)->name : null,
            'scores'     => $formattedScores,
            'total_matches' => $totalMatches,
            'total_tournaments' => $tournamentsCount,
            'total_prizes' => 0 // fix cứng vì chưa có đề bài
        ];
    }
}