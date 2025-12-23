<?php

namespace Database\Seeders;

use App\Models\CompetitionLocation;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Database\Seeder;

class FollowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email','tung19022003@gmail.com')->first();

        if (!$user) {
            $this->command->warn('⚠️ Không có user nào trong DB.');
            return;
        }
        $competitions = CompetitionLocation::take(3)->get();
        $otherUsers = User::where('id', '!=', $user->id)->take(2)->get();

        foreach ($competitions as $competition) {
            Follow::updateOrCreate([
                'user_id' => $user->id,
                'followable_id' => $competition->id,
                'followable_type' => CompetitionLocation::class,
            ]);
        }

        foreach ($otherUsers as $other) {
            Follow::updateOrCreate([
                'user_id' => $user->id,
                'followable_id' => $other->id,
                'followable_type' => User::class,
            ]);
        }
    }
}
