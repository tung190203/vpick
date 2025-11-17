<?php

namespace App\Http\Resources;

use App\Models\UserSportScore;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSportScoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $types = [
            UserSportScore::PERSONAL_SCORE,
            UserSportScore::DUPR_SCORE,
            UserSportScore::VNDUPR_SCORE,
        ];

        $result = [];

        foreach ($types as $type) {
            $result[$type] = [
                'score_type' => $type,
                'score_value' => $this->score_type === $type ? $this->score_value : null,
            ];
        }

        return $result;
    }
}
