<?php

namespace App\Http\Requests\Club;

use App\Enums\ClubMemberRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveJoinRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'sometimes|integer|exists:users,id',
            'role' => ['sometimes', Rule::enum(ClubMemberRole::class)],
        ];
    }
}
