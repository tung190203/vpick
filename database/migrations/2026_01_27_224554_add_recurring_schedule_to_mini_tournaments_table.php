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
            $table->string('recurring_schedule')->nullable()->after('repeat_type')->comment('Lịch lặp lại cho kèo cố định (ví dụ: "3-5-7" = thứ 3, 5, 7 hàng tuần)');
            $table->index('recurring_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mini_tournaments', function (Blueprint $table) {
            $table->dropIndex(['recurring_schedule']);
            $table->dropColumn('recurring_schedule');
        });
    }
};
