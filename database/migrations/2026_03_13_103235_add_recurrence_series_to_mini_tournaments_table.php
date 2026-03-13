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
            if (!Schema::hasColumn('mini_tournaments', 'recurrence_series_id')) {
                $table->uuid('recurrence_series_id')->nullable()->after('recurring_schedule')
                    ->comment('UUID để nhóm các tournament lặp lại cùng series');
                $table->index('recurrence_series_id');
            }
            if (!Schema::hasColumn('mini_tournaments', 'recurrence_series_cancelled_at')) {
                $table->datetime('recurrence_series_cancelled_at')->nullable()->after('recurrence_series_id')
                    ->comment('Thời gian series bị hủy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mini_tournaments', function (Blueprint $table) {
            if (Schema::hasColumn('mini_tournaments', 'recurrence_series_id')) {
                // Drop unique constraint if it exists
                try {
                    $table->dropUnique(['recurrence_series_id']);
                } catch (\Exception $e) {
                    // Ignore if constraint doesn't exist
                }
                $table->dropIndex(['recurrence_series_id']);
                $table->dropColumn('recurrence_series_id');
            }
            if (Schema::hasColumn('mini_tournaments', 'recurrence_series_cancelled_at')) {
                $table->dropColumn('recurrence_series_cancelled_at');
            }
        });
    }
};
