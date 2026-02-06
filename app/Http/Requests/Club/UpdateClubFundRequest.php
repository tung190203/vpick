<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClubFundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qr_code_url' => 'required|string|url',
        ];
    }
}
