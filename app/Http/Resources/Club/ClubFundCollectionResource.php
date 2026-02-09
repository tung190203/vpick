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
            'amount_per_member' => (float) ($this->amount_per_member ?? $this->target_amount),
            'collected_amount' => (float) $this->collected_amount,
            'currency' => $this->currency,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status,
            'qr_code_url' => $this->qr_code_url,
            'created_by' => $this->created_by,
            'progress_percentage' => $this->when(isset($this->progress_percentage), $this->progress_percentage),
            'contributions_count' => $this->when(
                isset($this->contributions_count),
                (int) $this->contributions_count,
                $this->whenLoaded('contributions', fn() => $this->contributions->count())
            ),
            'confirmed_count' => $this->when(
                isset($this->confirmed_count),
                (int) $this->confirmed_count,
                $this->whenLoaded('contributions', fn() => $this->contributions->where('status', 'confirmed')->count())
            ),
            'pending_count' => $this->when(
                isset($this->pending_count),
                (int) $this->pending_count,
                $this->whenLoaded('contributions', fn() => $this->contributions->where('status', 'pending')->count())
            ),
            'assigned_members_count' => $this->whenLoaded('assignedMembers', fn() => $this->assignedMembers->count()),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'contributions' => ClubFundContributionResource::collection($this->whenLoaded('contributions')),
            'pending_contributions' => $this->when(
                $this->relationLoaded('contributions'),
                fn() => ClubFundContributionResource::collection(
                    $this->contributions->filter(function ($contribution) {
                        return $contribution->status === \App\Enums\ClubFundContributionStatus::Pending;
                    })->values()
                )
            ),
            'assigned_members' => UserResource::collection($this->whenLoaded('assignedMembers')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
