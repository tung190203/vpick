<?php

namespace App\Http\Requests\Club;

use App\Enums\ClubActivityParticipantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ClubActivityParticipantStatus::class)],
        ];
    }
}
