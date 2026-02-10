<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFundCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clubId = (int) $this->route('clubId');

        return [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'target_amount' => 'sometimes|numeric|min:0.01',
            'amount_per_member' => 'nullable|numeric|min:0',
            'end_date' => 'nullable|date',
            'qr_code_url' => 'nullable|string',
            'qr_code_id' => [
                'nullable',
                'integer',
                Rule::exists('club_fund_collections', 'id')
                    ->where('club_id', $clubId)
                    ->whereNotNull('qr_code_url'),
            ],
        ];
    }
}
