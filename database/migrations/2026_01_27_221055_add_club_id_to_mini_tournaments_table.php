<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mini_tournaments', function (Blueprint $table) {
            $table->unsignedBigInteger('club_id')->nullable()->after('sport_id')->comment('CLB tạo kèo (nếu có)');
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('set null');
            $table->index('club_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mini_tournaments', function (Blueprint $table) {
            $table->dropForeign(['club_id']);
            $table->dropIndex(['club_id']);
            $table->dropColumn('club_id');
        });
    }
};
