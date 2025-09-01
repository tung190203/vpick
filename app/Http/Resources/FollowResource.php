<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FollowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $followable = $this->whenLoaded('followable');

        return [
            'id'   => $this->id,
            'type' => $followable ? strtolower(class_basename($followable)) : null,
            'data' => $followable ? $this->resolveFollowableResource($followable) : null,
        ];
    }

    protected function resolveFollowableResource($followable)
    {
        $classBase = class_basename($followable);
        $resourceClass = "\\App\\Http\\Resources\\{$classBase}Resource";

        if (class_exists($resourceClass)) {
            return new $resourceClass($followable);
        }

        return [
            'id'   => $followable->id ?? null,
            'name' => $followable->name ?? null,
        ];
    }
}
