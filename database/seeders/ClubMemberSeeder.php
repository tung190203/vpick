<?php

namespace Database\Seeders;

use App\Models\Club\Club;
use App\Models\Club\ClubMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClubMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $clubs = Club::all();

        foreach ($clubs as $club) {
            // Lấy 3-5 user random cho mỗi club
            $members = $users->shuffle()->take(rand(3,5));

            foreach ($members as $user) {
                // Kiểm tra xem user đã là member chưa
                ClubMember::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'club_id' => $club->id,
                    ],
                    [
                        'joined_at' => now()->subDays(rand(1, 730)), // random 2 năm trở lại
                        'is_manager' => rand(0,100) < 20, // 20% chance là manager
                    ]
                );
            }
        }
    }
}
