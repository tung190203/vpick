<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class ClubExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'title' => $this->title,
            'amount' => (float) $this->amount,
            'wallet_transaction_id' => $this->wallet_transaction_id,
            'spent_by' => $this->spent_by,
            'spent_at' => $this->spent_at?->toISOString(),
            'note' => $this->note,
            'spender' => new UserResource($this->whenLoaded('spender')),
            'wallet_transaction' => new ClubWalletTransactionResource($this->whenLoaded('walletTransaction')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
