<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'full_name' => $this->full_name,
            'email' => $this->email,
            'avatar_url' => $this->avatar_url,
            'location_id' => $this->location_id,
            'about' => $this->about,
            'role' => $this->role,
            'tier' => $this->tier,
            'vndupr_score' => $this->vndupr_score,
            'email_verified_at' => $this->email_verified_at,
        ];
    }
}
