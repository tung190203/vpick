<?php

namespace Database\Factories;

use App\Models\Club\Club;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Club\Club>
 */
class ClubFactory extends Factory
{
    protected $model = Club::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'address' => $this->faker->address(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'logo_url' => $this->faker->imageUrl(200, 200, 'sports', true, 'Club Logo'),
            'status' => 'active',
            'is_verified' => false,
            'created_by' => User::inRandomOrder()->first()?->id,
        ];
    }
}
