<?php

namespace App\Http\Requests\Club;

use App\Enums\ClubActivityFeeSplitType;
use App\Rules\ValidRecurringSchedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Convert empty strings to null for numeric fields
        $numericFields = ['latitude', 'longitude', 'fee_amount', 'guest_fee', 'penalty_amount', 'duration', 'max_participants', 'reminder_minutes'];
        $data = [];

        foreach ($numericFields as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $data[$field] = null;
            }
        }

        if (!empty($data)) {
            $this->merge($data);
        }

        if ($this->has('is_public')) {
            $this->merge([
                'is_public' => filter_var($this->is_public, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true
            ]);
        }
        if ($this->has('allow_member_invite')) {
            $this->merge([
                'allow_member_invite' => filter_var($this->allow_member_invite, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|in:meeting,practice,match,tournament,event,other',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'duration' => 'nullable|integer|min:1',
            'address' => 'required|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'cancellation_deadline' => 'nullable|date|before:start_time',
            'cancellation_deadline_hours' => 'nullable|integer|min:1|max:168',
            'mini_tournament_id' => 'nullable|exists:mini_tournaments,id',
            'recurring_schedule' => ['nullable', 'array', new ValidRecurringSchedule()],
            'reminder_minutes' => 'sometimes|integer|min:0',
            'fee_amount' => 'nullable|numeric|min:0',
            'fee_description' => 'nullable|string|max:1000',
            'guest_fee' => 'nullable|numeric|min:0',
            'penalty_amount' => 'nullable|numeric|min:0',
            'fee_split_type' => ['sometimes', Rule::enum(ClubActivityFeeSplitType::class)],
            'allow_member_invite' => 'sometimes|boolean',
            'is_public' => 'sometimes|boolean',
            'max_participants' => 'nullable|integer|min:1',
            'qr_image' => 'nullable|image|mimes:png,jpg,jpeg|max:5120',
        ];
    }
}
