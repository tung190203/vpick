<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class ClubFundCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'title' => $this->title,
            'description' => $this->description,
            'target_amount' => (float) $this->target_amount,
            'collected_amount' => (float) $this->collected_amount,
            'currency' => $this->currency,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status,
            'qr_code_url' => $this->qr_code_url,
            'created_by' => $this->created_by,
            'progress_percentage' => $this->when(isset($this->progress_percentage), $this->progress_percentage),
            'contributions_count' => $this->whenLoaded('contributions', fn() => $this->contributions->count()),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'contributions' => ClubFundContributionResource::collection($this->whenLoaded('contributions')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
