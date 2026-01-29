<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class ClubWalletTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_wallet_id' => $this->club_wallet_id,
            'direction' => $this->direction,
            'amount' => (float) $this->amount,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'payment_method' => $this->payment_method,
            'status' => $this->status,
            'reference_code' => $this->reference_code,
            'description' => $this->description,
            'created_by' => $this->created_by,
            'confirmed_by' => $this->confirmed_by,
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'wallet' => new ClubWalletResource($this->whenLoaded('wallet')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'confirmer' => new UserResource($this->whenLoaded('confirmer')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
