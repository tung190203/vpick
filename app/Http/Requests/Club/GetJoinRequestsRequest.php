<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class GetJoinRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'status' => 'sometimes|in:pending,approved,rejected',
        ];
    }
}
