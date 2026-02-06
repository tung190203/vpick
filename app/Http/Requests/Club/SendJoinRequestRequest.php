<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class SendJoinRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'nullable|string|max:500',
        ];
    }
}
