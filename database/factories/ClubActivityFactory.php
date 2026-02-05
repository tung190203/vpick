<?php

namespace Database\Factories;

use App\Enums\ClubActivityFeeSplitType;
use App\Enums\ClubActivityStatus;
use App\Models\Club\Club;
use App\Models\Club\ClubActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Club\ClubActivity>
 */
class ClubActivityFactory extends Factory
{
    protected $model = ClubActivity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'club_id' => Club::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['meeting', 'practice', 'match', 'tournament', 'event', 'other']),
            'start_time' => $this->faker->dateTimeBetween('now', '+30 days'),
            'end_time' => $this->faker->dateTimeBetween('+1 hour', '+32 days'),
            'address' => $this->faker->address(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'reminder_minutes' => 15,
            'status' => ClubActivityStatus::Scheduled,
            'created_by' => User::factory(),
            'fee_amount' => 0,
            'guest_fee' => 0,
            'penalty_percentage' => 0,
            'fee_split_type' => ClubActivityFeeSplitType::Fixed,
            'allow_member_invite' => false,
            'is_public' => true,
        ];
    }
}
