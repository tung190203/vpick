<?php

namespace Database\Seeders;

use App\Models\Club\ClubNotificationType;
use Illuminate\Database\Seeder;

class ClubNotificationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Thông báo chung', 'slug' => 'general', 'description' => 'Thông báo chung của CLB', 'icon' => 'bell', 'is_active' => true],
            ['name' => 'Sự kiện', 'slug' => 'event', 'description' => 'Thông báo sự kiện, lịch tập', 'icon' => 'calendar', 'is_active' => true],
            ['name' => 'Tài chính', 'slug' => 'finance', 'description' => 'Thu chi, đóng phí', 'icon' => 'currency', 'is_active' => true],
            ['name' => 'Thành viên', 'slug' => 'member', 'description' => 'Thành viên mới, thay đổi vai trò', 'icon' => 'users', 'is_active' => true],
            ['name' => 'Khẩn cấp', 'slug' => 'urgent', 'description' => 'Thông báo khẩn cấp', 'icon' => 'alert', 'is_active' => true],
        ];

        foreach ($types as $type) {
            ClubNotificationType::firstOrCreate(
                ['slug' => $type['slug']],
                $type
            );
        }
    }
}
