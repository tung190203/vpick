<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tạo profile cho các CLB chưa có profile (CLB cũ được tạo trước khi luôn tạo profile).
     */
    public function up(): void
    {
        $clubIdsWithoutProfile = DB::table('clubs')
            ->whereNotIn('id', DB::table('club_profiles')->pluck('club_id'))
            ->pluck('id');

        foreach ($clubIdsWithoutProfile as $clubId) {
            DB::table('club_profiles')->insert([
                'club_id' => $clubId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không xóa - các profile đã tạo có thể chứa dữ liệu người dùng đã cập nhật
    }
};
