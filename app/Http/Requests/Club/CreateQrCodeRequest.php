<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class CreateQrCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image'     => 'required|image|mimes:png,jpg,jpeg,gif|max:5120',
            'qr_note'   => 'nullable|string|max:1000',
        ];
    }
}
