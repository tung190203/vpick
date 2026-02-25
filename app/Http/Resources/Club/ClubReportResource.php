<?php

namespace App\Http\Resources\Club;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'user_id' => $this->user_id,
            'reason_type' => $this->reason_type,
            'reason_label' => $this->reason_type?->label(),
            'reason' => $this->description,
            'status' => $this->status,
            'status_label' => $this->status?->label(),
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'admin_note' => $this->when(isset($this->admin_note), $this->admin_note),
            'reporter' => new UserResource($this->whenLoaded('reporter')),
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
