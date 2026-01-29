<?php

namespace Database\Seeders;

use App\Models\Club\Club;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seed dữ liệu mẫu cho module clubs (không seed clubs, club_members).
 * Chạy riêng: php artisan db:seed --class=ClubModuleSeeder
 * Cần có sẵn User và Club có active members (tạo từ app hoặc tay).
 */
class ClubModuleSeeder extends Seeder
{
    public function run(): void
    {
        if (User::count() === 0) {
            $this->command->warn('Chưa có User. Cần tạo user trước.');
            return;
        }

        if (Club::count() === 0) {
            $this->command->warn('Chưa có Club. Cần tạo club và members từ app trước.');
            return;
        }

        $this->call([
            ClubNotificationTypeSeeder::class,
            ClubFakeDataSeeder::class,
        ]);
    }
}
