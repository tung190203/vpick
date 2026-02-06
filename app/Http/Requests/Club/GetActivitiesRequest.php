<?php

namespace App\Http\Requests\Club;

use Illuminate\Foundation\Http\FormRequest;

class GetActivitiesRequest extends FormRequest
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
            'type' => 'sometimes|in:meeting,practice,match,tournament,event,other',
            'statuses' => 'sometimes|array',
            'statuses.*' => 'sometimes|in:all,registered,available,scheduled,ongoing,completed,cancelled',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ];
    }
}
