<?php

namespace App\Http\Requests\Club;

use App\Enums\ClubStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClubRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_public')) {
            $this->merge([
                'is_public' => filter_var($this->is_public, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:clubs,name,NULL,id,deleted_at,NULL',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'logo_url' => 'nullable|image|max:2048',
            'cover_image_url' => 'nullable|image|max:2048',
            'description' => 'nullable|string|max:5000',
            'status' => ['nullable', Rule::enum(ClubStatus::class)],
            'is_public' => 'nullable|boolean',
        ];
    }
}
