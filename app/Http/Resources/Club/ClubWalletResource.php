<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubWalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'type' => $this->type,
            'currency' => $this->currency,
            'balance' => $this->balance ?? 0,
            'qr_code_url' => $this->qr_code_url,
            'transaction_count' => $this->when(isset($this->transaction_count), $this->transaction_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
