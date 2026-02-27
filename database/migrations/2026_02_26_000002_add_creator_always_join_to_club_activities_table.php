<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * creator_always_join: true = người tạo tự động tham gia; false = người tạo đăng ký từng ngày như member.
     */
    public function up(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->boolean('creator_always_join')->default(true)->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->dropColumn('creator_always_join');
        });
    }
};
