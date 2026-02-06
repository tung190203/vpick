<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class VerifyClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_verified' => 'required|boolean',
        ];
    }
}
