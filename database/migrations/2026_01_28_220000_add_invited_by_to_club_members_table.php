<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * invited_by: Admin mời user vào CLB → user phải đồng ý mới thành member.
     * null = user tự gửi yêu cầu tham gia (chờ admin duyệt).
     */
    public function up(): void
    {
        Schema::table('club_members', function (Blueprint $table) {
            if (!Schema::hasColumn('club_members', 'invited_by')) {
                $table->unsignedBigInteger('invited_by')->nullable()->after('user_id')
                    ->comment('Admin mời vào CLB; null = user tự gửi request');
                $table->foreign('invited_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_members', function (Blueprint $table) {
            if (Schema::hasColumn('club_members', 'invited_by')) {
                $table->dropForeign(['invited_by']);
                $table->dropColumn('invited_by');
            }
        });
    }
};
