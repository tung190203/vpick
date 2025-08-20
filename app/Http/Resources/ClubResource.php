<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class ClubResource extends JsonResource
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
            'name' => $this->name,
            'location' => $this->location,
            'logo_url' => $this->logo_url,
            'created_by' => $this->created_by,
            'members' => $this->whenLoaded('members', function () {
                return $this->members->map(function ($member) {
                    $user = new UserResource($member);
                    return array_merge($user->toArray(request()), [
                        'is_manager' => (bool) $member->pivot->is_manager,
                    ]);
                });
            }),
            'quantity_members' => $this->members->count(),
            'highest_score' => $this->members->max('vndupr_score') ?? 0,
        ];
    }
}
