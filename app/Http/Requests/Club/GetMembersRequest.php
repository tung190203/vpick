<?php

namespace App\Http\Requests\Club;

use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetMembersRequest extends FormRequest
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
            'search' => 'sometimes|string|max:255',
            'role' => ['sometimes', Rule::enum(ClubMemberRole::class)],
            'status' => ['sometimes', Rule::enum(ClubMemberStatus::class)],
        ];
    }
}
