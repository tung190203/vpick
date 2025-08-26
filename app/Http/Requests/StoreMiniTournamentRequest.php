<?php

namespace App\Http\Requests;

use App\Models\MiniTournament;
use Illuminate\Foundation\Http\FormRequest;

class StoreMiniTournamentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'sport_id' => 'required|exists:sports,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',

            'match_type' => 'required|integer|in:' . implode(',', MiniTournament::MATCH_TYPE_NUMBER),

            'starts_at' => 'nullable|date',
            'duration_minutes' => 'nullable|integer|min:1',
            'competition_location_id' => 'nullable|exists:competition_locations,id',

            'is_private' => 'boolean',
            'fee_amount' => 'nullable|integer|min:0',
            'max_players' => 'nullable|integer|min:1',

            'enable_dupr' => 'boolean',
            'enable_vndupr' => 'boolean',

            'min_rating' => 'nullable|numeric|min:0',
            'max_rating' => 'nullable|numeric|min:0',

            'gender_policy' => 'required|integer|in:' .implode(',', MiniTournament::GENDER),
            'min_age' => 'nullable|integer|min:1',
            'max_age' => 'nullable|integer|min:1',

            'repeat_type' => 'required|integer|in:' . implode(',', MiniTournament::REPEAT),
            'role_type' => 'required|integer|in:' . implode(',', MiniTournament::ROLE),
            'lock_cancellation' => 'boolean',

            'auto_approve' => 'boolean',
            'allow_participant_add_friends' => 'boolean',
            'send_notification' => 'boolean',

            'status' => 'required|integer|in:' . implode(',', MiniTournament::STATUS)
        ];
    }
}
