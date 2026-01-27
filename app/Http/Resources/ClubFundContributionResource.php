<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubFundContributionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_fund_collection_id' => $this->club_fund_collection_id,
            'user_id' => $this->user_id,
            'amount' => (float) $this->amount,
            'wallet_transaction_id' => $this->wallet_transaction_id,
            'status' => $this->status,
            'user' => new UserListResource($this->whenLoaded('user')),
            'wallet_transaction' => new ClubWalletTransactionResource($this->whenLoaded('walletTransaction')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
