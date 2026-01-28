<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListClubResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'logo_url' => $this->logo_url,
            'is_verified' => (bool) $this->is_verified,
            'created_by' => $this->created_by,
            'quantity_members' => $this->whenLoaded('members', fn() => $this->members->count(), 0),
            'skill_level' => $this->whenLoaded('members', function () {
                $scores = $this->members
                    ->map(fn($member) => $member->user?->vnduprScores?->max('score_value'))
                    ->filter(fn($score) => $score !== null);

                if ($scores->isEmpty()) {
                    return null;
                }

                return [
                    'min' => round($scores->min(), 1),
                    'max' => round($scores->max(), 1),
                ];
            }, null),
        ];
    }
}
