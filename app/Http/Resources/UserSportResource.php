<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $types = ['personal_score', 'dupr_score', 'vndupr_score'];

        // Lấy scores theo type
        $scoresByType = $this->whenLoaded('scores')
            ? $this->scores->keyBy('score_type')
            : collect();

        // Build object dạng key => value
        $formattedScores = [];
        foreach ($types as $type) {
            $score = $scoresByType->get($type);
            $formattedScores[$type] = number_format($score?->score_value ?? 0, 2);
        }

        return [
            'sport_id'   => $this->sport_id,
            'sport_icon' => $this->relationLoaded('sport') ? optional($this->sport)->icon : null,
            'sport_name' => $this->relationLoaded('sport') ? optional($this->sport)->name : null,
            'scores'     => $formattedScores,
        ];
    }
}
