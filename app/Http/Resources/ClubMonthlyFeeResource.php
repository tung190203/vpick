<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClubMonthlyFeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'due_day' => $this->due_day,
            'is_active' => $this->is_active,
            'payments_count' => $this->whenLoaded('payments', fn() => $this->payments->count()),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
