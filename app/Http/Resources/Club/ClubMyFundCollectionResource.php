<?php

namespace App\Http\Resources\Club;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource cho từng item trong GET my-collections (need_payment, pending, confirmed).
 * Mỗi item là đợt thu + trạng thái đóng tiền của user hiện tại.
 */
class ClubMyFundCollectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'title' => $this->resource['title'],
            'description' => $this->resource['description'],
            'amount_due' => (float) $this->resource['amount_due'],
            'currency' => $this->resource['currency'],
            'end_date' => $this->resource['end_date'],
            'status' => $this->resource['status'],
            'qr_code_url' => $this->resource['qr_code_url'],
            'my_contribution' => $this->resource['my_contribution'],
            'payment_status' => $this->resource['payment_status'],
            'is_overdue' => (bool) ($this->resource['is_overdue'] ?? false),
        ];
    }
}
