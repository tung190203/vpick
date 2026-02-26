<?php

namespace App\Http\Resources\Club;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class ClubMemberUserResource extends UserResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        unset($data['clubs']);

        // User đang gọi API = đang online
        if ($request->user() && $this->id === $request->user()->id) {
            $data['is_online'] = true;
            $data['last_login'] = now()->toISOString();
        }

        return $data;
    }
}
