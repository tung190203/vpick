<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'collection_id' => [
                'nullable',
                'integer',
                Rule::exists('club_fund_collections', 'id')
                    ->where('club_id', (int) $this->route('clubId')),
            ],
            'content' => 'nullable|string|max:300',
        ];
    }
}
