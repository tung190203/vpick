<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class GetClubsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'radius' => 'nullable|numeric|min:1',
            'minLat' => 'nullable|numeric',
            'maxLat' => 'nullable|numeric',
            'minLng' => 'nullable|numeric',
            'maxLng' => 'nullable|numeric',
            'perPage' => 'sometimes|integer|min:1|max:200',
        ];
    }
}
