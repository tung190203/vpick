<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->string('recurrence_series_id', 64)->nullable()->after('recurring_schedule');
            $table->timestamp('recurrence_series_cancelled_at')->nullable()->after('recurrence_series_id');

            $table->index('recurrence_series_id');
        });
    }

    public function down(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->dropIndex(['recurrence_series_id']);
            $table->dropColumn(['recurrence_series_id', 'recurrence_series_cancelled_at']);
        });
    }
};
