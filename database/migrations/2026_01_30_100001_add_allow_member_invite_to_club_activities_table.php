<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Cho phép thành viên thường mời thêm khách mời vào sự kiện.
     */
    public function up(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->boolean('allow_member_invite')->default(false)->after('fee_split_type')
                ->comment('Cho phép thành viên (không chỉ admin/manager/secretary) mời thêm người tham gia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->dropColumn('allow_member_invite');
        });
    }
};
