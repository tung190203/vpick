<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Giới hạn số chỗ tham gia (hiển thị X/Y trên card).
     */
    public function up(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->unsignedInteger('max_participants')->nullable()->after('allow_member_invite')
                ->comment('Số chỗ tối đa (null = không giới hạn)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->dropColumn('max_participants');
        });
    }
};
