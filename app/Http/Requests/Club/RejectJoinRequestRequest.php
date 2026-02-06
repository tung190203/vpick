<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class RejectJoinRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'sometimes|integer|exists:users,id',
            'rejection_reason' => 'nullable|string|max:500',
        ];
    }
}
