<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class CancelActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => 'required|string|max:500',
            'cancel_transactions' => 'required|boolean',
        ];
    }
}
