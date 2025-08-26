<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MiniMatchResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $groupedResults = [];
        if ($this->relationLoaded('results')) {
            $groupedResults = $this->results
                ->groupBy('set_number')
                ->mapWithKeys(function ($set, $setNumber) {
                    return [
                        "set{$setNumber}" => MiniMatchResultResource::collection($set)
                    ];
                })->toArray();
        }

        return [
            'id' => $this->id,
            'mini_tournament_id' => $this->mini_tournament_id,
            'round' => $this->round,
            'participant1' => new MiniParticipantResource($this->whenLoaded('participant1')),
            'participant2' => new MiniParticipantResource($this->whenLoaded('participant2')),
            'scheduled_at' => $this->scheduled_at,
            'referee_id' => $this->referee_id,
            'status' => $this->status,
            'participant_win_id' => $this->participant_win_id,
            'participant_win' => new MiniParticipantResource($this->whenLoaded('participantWin')),
            'participant1_confirm' => $this->participant1_confirm,
            'participant2_confirm' => $this->participant2_confirm,
            'results_by_sets' => $groupedResults,
        ];
    }
}
