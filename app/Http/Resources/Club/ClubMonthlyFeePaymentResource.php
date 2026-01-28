<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserListResource;

class ClubMonthlyFeePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'club_monthly_fee_id' => $this->club_monthly_fee_id,
            'user_id' => $this->user_id,
            'period' => $this->period->format('Y-m-d'),
            'amount' => (float) $this->amount,
            'wallet_transaction_id' => $this->wallet_transaction_id,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toISOString(),
            'user' => new UserListResource($this->whenLoaded('user')),
            'monthly_fee' => new ClubMonthlyFeeResource($this->whenLoaded('monthlyFee')),
            'wallet_transaction' => new ClubWalletTransactionResource($this->whenLoaded('walletTransaction')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
