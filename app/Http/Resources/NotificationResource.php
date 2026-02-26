<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->data ?? [];
        $type = $data['type'] ?? class_basename($this->type);

        return [
            'id' => $this->id,
            'type' => $type,
            'data' => $data,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
