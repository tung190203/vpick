<?php

namespace Database\Factories;

use App\Models\CompetitionLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class CompetitionLocationYardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'competition_location_id' => CompetitionLocation::inRandomOrder()->first()?->id,
            'yard_number' => $this->faker->numberBetween(1, 10),
        ];
    }
}
