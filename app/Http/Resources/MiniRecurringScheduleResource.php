<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MiniRecurringScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mini_tournament_id' => $this->mini_tournament_id,
            'repeat_type' => $this->repeat_type,
            'repeat_type_text' => $this->repeat_type_text,
            'repeat_days' => $this->repeat_days,
            'repeat_days_array' => $this->repeat_days_array,
            'repeat_days_text' => $this->repeat_days_text,
            'time' => $this->time,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
