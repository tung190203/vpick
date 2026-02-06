<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class StoreFundCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required_without:description|nullable|string|max:255',
            'description' => 'required_without:title|nullable|string',
            'target_amount' => 'required|numeric|min:0.01',
            'amount_per_member' => 'nullable|numeric|min:0.01',
            'member_ids' => 'required|array|min:1',
            'member_ids.*' => 'exists:users,id',
            'currency' => 'sometimes|string|max:3',
            'start_date' => 'required|date',
            'deadline' => 'nullable|date|after_or_equal:start_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'qr_code_url' => 'nullable|string',
        ];
    }
}
