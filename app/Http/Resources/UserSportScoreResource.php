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
        return [
            'score_type' => $this->score_type,
            'score_value' => $this->score_value,
        ];
    }
}
