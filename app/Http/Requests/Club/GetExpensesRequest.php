<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class GetExpensesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'spent_by' => 'sometimes|exists:users,id',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ];
    }
}
