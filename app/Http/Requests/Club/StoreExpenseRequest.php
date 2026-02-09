<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:65535',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,qr_code,other',
            'spent_at' => 'nullable|date',
            'note' => 'nullable|string',
            'reference_code' => 'nullable|string|max:255',
        ];
    }
}
