<?php

namespace App\Http\Requests\Club;

use App\Enums\ClubReportReasonType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClubReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason_type' => ['required', Rule::enum(ClubReportReasonType::class)],
            'reason' => 'nullable|string|max:500',
        ];
    }
}
