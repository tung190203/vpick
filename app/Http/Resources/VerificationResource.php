<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'vndupr_score' => $this->vndupr_score,
            'certified_file' => $this->certified_file ? asset('storage/' . $this->certified_file) : null,
            'verifier_id' => $this->verifier_id,
            'verifier' => new UserResource($this->whenLoaded('verifier')),
            'approver_id' => $this->approver_id,
            'approver' => new UserResource($this->whenLoaded('approver')),
            'status' => $this->status,
        ];
    }
}
