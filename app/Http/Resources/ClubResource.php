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
            'id'                => $this->id,
            'name'              => $this->name,
            'location'          => $this->location,
            'logo_url'          => $this->logo_url,
            'created_by'        => $this->created_by,
            'member'            => UserResource::collection($this->whenLoaded('member'))
        ];
    }
}
