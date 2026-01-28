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
        Schema::table('club_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('mini_tournament_id')->nullable()->after('club_id')->comment('Link đến kèo đấu (nếu activity là một kèo)');
            $table->boolean('is_recurring')->default(false)->after('type')->comment('Kèo cố định (lặp lại)');
            $table->string('recurring_schedule')->nullable()->after('is_recurring')->comment('Lịch lặp lại (ví dụ: "3-5-7" = thứ 3, 5, 7)');
            
            $table->foreign('mini_tournament_id')->references('id')->on('mini_tournaments')->onDelete('set null');
            $table->index('mini_tournament_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->dropForeign(['mini_tournament_id']);
            $table->dropIndex(['mini_tournament_id']);
            $table->dropColumn(['mini_tournament_id', 'is_recurring', 'recurring_schedule']);
        });
    }
};
