<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UserBadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $badges = Badge::all();

        foreach ($users as $user) {
            // Random 1-3 badges cho mỗi user
            $assignedBadges = $badges->shuffle()->take(rand(1, 3));

            foreach ($assignedBadges as $badge) {
                // firstOrCreate để tránh duplicate key
                UserBadge::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'badge_id' => $badge->id,
                    ],
                    [
                        'awarded_at' => Carbon::now()->subDays(rand(0, 365)),
                    ]
                );
            }
        }
    }
}
