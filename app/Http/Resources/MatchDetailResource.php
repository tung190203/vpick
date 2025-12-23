<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Matches;
use App\Models\TournamentType;

class MatchDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $homeTeam = $this->homeTeam;
        $awayTeam = $this->awayTeam;
        $tournamentType = $this->tournamentType;

        $roundNumber = Matches::where('tournament_type_id', $this->tournament_type_id)
            ->where('id', '<=', $this->id)
            ->count();

        $roundName = "Trận đấu số {$roundNumber}";

        // Mặc định rỗng
        $legs = collect();

        if ($tournamentType) {
            switch ($tournamentType->format) {

                case TournamentType::FORMAT_ROUND_ROBIN:
                    $sets = $this->results->isEmpty()
                        ? (object)[]
                        : $this->results
                            ->groupBy('set_number')
                            ->mapWithKeys(fn ($setGroup, $setNumber) => [
                                'set_' . $setNumber => $setGroup->map(fn ($r) => [
                                    'team_id' => $r->team_id,
                                    'score' => $r->score,
                                    'won_match' => $r->won_match,
                                ])->values(),
                            ])
                            ->toArray();

                    $legs = collect([
                        ['sets' => $sets],
                    ]);
                    break;

                case TournamentType::FORMAT_ELIMINATION:
                case TournamentType::FORMAT_MIXED:
                default:
                    if ($this->home_team_id && $this->away_team_id) {
                        $legs = Matches::with('results')
                            ->where('tournament_type_id', $this->tournament_type_id)
                            ->where(function ($q) {
                                $q->where(function ($sub) {
                                    $sub->where('home_team_id', $this->home_team_id)
                                        ->where('away_team_id', $this->away_team_id);
                                })->orWhere(function ($sub) {
                                    $sub->where('home_team_id', $this->away_team_id)
                                        ->where('away_team_id', $this->home_team_id);
                                });
                            })
                            ->orderBy('leg')
                            ->get()
                            ->map(function ($match) {

                                $sets = $match->results->isEmpty()
                                    ? (object)[]
                                    : $match->results
                                        ->groupBy('set_number')
                                        ->mapWithKeys(fn ($setGroup, $setNumber) => [
                                            'set_' . $setNumber => $setGroup->map(fn ($r) => [
                                                'team_id' => $r->team_id,
                                                'score' => $r->score,
                                                'won_match' => $r->won_match,
                                            ])->values(),
                                        ])
                                        ->toArray();

                                return [
                                    'leg' => $match->leg,
                                    'sets' => $sets,
                                ];
                            });
                    }
                    break;
            }
        }

        return [
            'id' => $this->id,
            'name_of_match' => $this->name_of_match,
            'round' => $roundNumber,
            'round_name' => $roundName,
            'home_team' => $homeTeam ? [
                'id' => $homeTeam->id,
                'name' => $homeTeam->name,
                'members' => $homeTeam->members->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->full_name,
                    'avatar' => $m->avatar_url,
                ]),
            ] : null,
            'away_team' => $awayTeam ? [
                'id' => $awayTeam->id,
                'name' => $awayTeam->name,
                'members' => $awayTeam->members->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->full_name,
                    'avatar' => $m->avatar_url,
                ]),
            ] : null,
            'legs' => $legs,
            'is_bye' => $this->is_bye,
            'is_loser_bracket' => $this->is_loser_bracket,
            'is_third_place' => $this->is_third_place,
            'court' => $this->court,
            'winner_id' => $this->winner_id,
            'status' => $this->status,
        ];
    }
}
