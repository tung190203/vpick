<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class InviteParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ];
    }
}
