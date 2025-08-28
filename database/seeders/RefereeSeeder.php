<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Referee;

class RefereeSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy users làm referee (ví dụ: 5 user đầu)
        $users = User::take(5)->get();

        foreach ($users as $user) {
            // Kiểm tra user đã có referee chưa (phòng trường hợp seed nhiều lần)
            if ($user->referee) {
                continue;
            }

            Referee::create([
                'user_id' => $user->id,
                'certified_by' => fake()->company(),
                'certification_lv' => fake()->randomElement(['A', 'B', 'C', 'local', 'pro']),
                'status' => fake()->randomElement(['pending','active','suspended']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
