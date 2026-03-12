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
        $rules = [
            'poster' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'sport_id' => 'required|exists:sports,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',

            // New fields: play_mode and format
            'play_mode' => 'required|integer|in:' . implode(',', MiniTournament::PLAY_MODE),
            // format is required when play_mode = 2 (Thi đấu)
            'format' => [
                'nullable',
                'integer',
                'in:' . implode(',', MiniTournament::FORMAT),
                function ($_, $value, $fail) {
                    if ($this->play_mode == MiniTournament::PLAY_MODE_COMPETITIVE && empty($value)) {
                        $fail('Thể thức là bắt buộc khi chế độ chơi là Thi đấu.');
                    }
                },
            ],

            // Keep match_type for backward compatibility but make nullable
            'match_type' => 'nullable|integer|in:' . implode(',', MiniTournament::MATCH_TYPE_NUMBER),

            'starts_at' => 'nullable|date',
            'duration_minutes' => 'nullable|integer|min:1',
            'competition_location_id' => 'nullable|exists:competition_locations,id',

            'is_private' => 'boolean',
            
            // Fee fields - new structure
            'has_fee' => 'boolean',
            'auto_split_court_fee' => 'boolean',
            'payment_note' => 'nullable|string|max:500',
            'qr_code_image' => 'nullable|string', // base64 or URL
            'payment_account_id' => 'nullable|exists:club_wallets,id',
            
            // Legacy fee fields (still supported for backward compatibility)
            'fee' => 'nullable|in:' . implode(',', MiniTournament::FEE),
            'fee_amount' => 'nullable|integer|min:0',
            'prize_pool' => 'nullable|integer|min:0',
            'max_players' => 'nullable|integer|min:1',

            // Dupr settings will be auto-set based on play_mode
            'enable_dupr' => 'boolean',
            'enable_vndupr' => 'boolean',

            'min_rating' => 'nullable|numeric|min:0',
            'max_rating' => 'nullable|numeric|min:0',
            'set_number' => 'required|integer|min:1',
            'games_per_set' => 'required|integer|min:11',
            'points_difference' => 'required|integer|min:1',
            'max_points' => 'required|integer|min:11',
            'court_switch_points' => 'required|integer|min:1',

            'gender_policy' => 'required|integer|in:' .implode(',', MiniTournament::GENDER),

            'repeat_type' => 'required|integer|in:' . implode(',', MiniTournament::REPEAT),
            'role_type' => 'required|integer|in:' . implode(',', MiniTournament::ROLE),
            'lock_cancellation' => 'required|in:' . implode(',', MiniTournament::LOCK_CANCELLATION),
            'age_group' => 'required|integer|in:' . implode(',', MiniTournament::AGE_GROUP),

            'auto_approve' => 'boolean',
            'allow_participant_add_friends' => 'boolean',
            'send_notification' => 'boolean',

            'status' => 'required|integer|in:' . implode(',', MiniTournament::STATUS),

            'invite_user' => 'nullable|array',
            'invite_user.*' => 'exists:users,id',
        ];

        // Custom validation: if has_fee is true, require fee details
        if ($this->has('has_fee') && $this->has_fee) {
            $rules['fee'] = 'required|in:' . implode(',', MiniTournament::FEE);
            $rules['fee_amount'] = 'required|integer|min:1';
        }

        return $rules;
    }

    /**
     * Prepare the data for validation.
     * Auto-set dupr settings based on play_mode.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('play_mode')) {
            $duprSettings = MiniTournament::getDefaultDuprSettings($this->play_mode);
            $this->merge([
                'enable_dupr' => $duprSettings['enable_dupr'],
                'enable_vndupr' => $duprSettings['enable_vndupr'],
            ]);
        }
    }
}
