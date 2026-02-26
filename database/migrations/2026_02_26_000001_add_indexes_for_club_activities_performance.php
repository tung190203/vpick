<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds indexes to optimize GET /api/clubs/{clubId}/activities API (timeout fix).
     */
    public function up(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            // Main query filter: club_id + status + start_time
            $table->index(['club_id', 'status', 'start_time'], 'club_activities_club_status_start_idx');
            // Recurring collapse subquery: club_id + title + start_time
            $table->index(['club_id', 'title', 'start_time'], 'club_activities_recurring_collapse_idx');
        });

        Schema::table('club_activity_participants', function (Blueprint $table) {
            // EXISTS is_registered subquery: club_activity_id + user_id + status
            $table->index(['club_activity_id', 'user_id', 'status'], 'club_activity_participants_activity_user_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->dropIndex('club_activities_club_status_start_idx');
            $table->dropIndex('club_activities_recurring_collapse_idx');
        });

        Schema::table('club_activity_participants', function (Blueprint $table) {
            $table->dropIndex('club_activity_participants_activity_user_status_idx');
        });
    }
};
