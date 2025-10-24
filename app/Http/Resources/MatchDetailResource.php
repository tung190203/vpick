<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Matches;

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
     
         // Mặc định rỗng
         $legs = collect();
     
         if ($tournamentType) {
             switch ($tournamentType->format) {
                case \App\Models\TournamentType::FORMAT_ROUND_ROBIN:
                    // 🎯 VÒNG TRÒN: chỉ sets của chính trận
                    $legs = collect([
                        [
                            'sets' => $this->results
                                ->groupBy('set_number')
                                ->map(function ($setGroup, $setNumber) {
                                    return [
                                        'set_' . $setNumber => $setGroup->map(fn($r) => [
                                            'team_id' => $r->team_id,
                                            'score' => $r->score,
                                            'won_match' => $r->won_match,
                                        ]),
                                    ];
                                })
                                ->values()
                        ],
                    ]);
                    break;                
     
                 case \App\Models\TournamentType::FORMAT_ELIMINATION:
                 case \App\Models\TournamentType::FORMAT_MIXED:
                 default:
                     // 🧱 ELIMINATION hoặc MIXED: gom theo cặp home/away (có thể đảo)
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
                             $sets = [];
                             foreach ($match->results as $r) {
                                 $setKey = 'set_' . $r->set_number;
                                 if (!isset($sets[$setKey])) {
                                     $sets[$setKey] = [];
                                 }
                                 $sets[$setKey][] = [
                                     'team_id' => $r->team_id,
                                     'score' => $r->score,
                                     'won_match' => $r->won_match,
                                 ];
                             }
                             return [
                                 'leg' => $match->leg,
                                 'sets' => $sets,
                             ];
                         });
                     break;
             }
         }
     
         return [
             'id' => $this->id,
             'round' => $this->round,
             'home_team' => [
                 'id' => $homeTeam->id,
                 'name' => $homeTeam->name,
                 'members' => $homeTeam->members->map(fn($m) => [
                     'id' => $m->id,
                     'name' => $m->full_name,
                     'avatar' => $m->avatar_url,
                 ]),
             ],
             'away_team' => [
                 'id' => $awayTeam->id,
                 'name' => $awayTeam->name,
                 'members' => $awayTeam->members->map(fn($m) => [
                     'id' => $m->id,
                     'name' => $m->full_name,
                     'avatar' => $m->avatar_url,
                 ]),
             ],
             'legs' => $legs,
             'is_bye' => $this->is_bye,
             'is_loser_bracket' => $this->is_loser_bracket,
             'is_third_place' => $this->is_third_place,
             'court' => $this->court,
             'winner_id' => $this->winner_id
         ];
     }
     
}
