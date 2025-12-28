<?php

namespace App\Http\Resources;

use App\Models\MiniTournamentStaff;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListMiniTournamentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $participants = $this->relationLoaded('participants') ? $this->participants : collect();
        return [
            'id' => $this->id,
            'poster' => $this->poster,
            'starts_at' => $this->starts_at,
            'sport' => new SportResource($this->whenLoaded('sport')),
            'name' => $this->name,
            'description' => $this->description,
            'competition_location' => new CompetitionLocationResource($this->whenLoaded('competitionLocation')),
            'status' => $this->status,
            'status_text' => $this->status_text,
            'staff' => $this->whenLoaded('staff', function () {
                return $this->staff
                    ->groupBy(fn($staff) => MiniTournamentStaff::getRoleText( $staff->pivot->role))
                    ->map(fn($group) => MiniTournamentStaffResource::collection($group));
            }),
            'participants' => MiniParticipantResource::collection($participants),
            'all_users' => UserListResource::collection($this->all_users ?? collect()),
        ];
    }
}
