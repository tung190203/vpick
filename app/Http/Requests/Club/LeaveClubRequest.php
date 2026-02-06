<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class LeaveClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transfer_to_user_id' => 'nullable|integer|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'transfer_to_user_id.exists' => 'Người dùng không tồn tại.',
        ];
    }
}
