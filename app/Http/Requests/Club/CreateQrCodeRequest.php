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
            'image' => 'required|image|mimes:png,jpg,jpeg,gif|max:5120',
            'amount' => 'required|numeric|min:0.01',
            'content' => 'required|string|max:300',
            'apply_to_other_clubs' => 'sometimes|boolean',
        ];
    }
}
