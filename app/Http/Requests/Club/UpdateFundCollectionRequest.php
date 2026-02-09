<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFundCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'target_amount' => 'sometimes|numeric|min:0.01',
            'amount_per_member' => 'nullable|numeric|min:0',
            'end_date' => 'nullable|date|after:start_date',
            'qr_code_url' => 'nullable|string',
        ];
    }
}
