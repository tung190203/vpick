<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sportsList = [
            'Pickleball',
            'Soccer',
            'Basketball',
            'Tennis',
            'Baseball',
            'Hockey',
            'Volleyball',
            'Cricket',
            'Rugby',
            'Golf',
            'Swimming'
        ];
        foreach ($sportsList as $sport) {
            Sport::firstOrCreate([
                'slug' => Str::slug($sport)
            ], [
                'name' => $sport,
                'icon' => fake()->imageUrl(64, 64, 'sports', true),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
