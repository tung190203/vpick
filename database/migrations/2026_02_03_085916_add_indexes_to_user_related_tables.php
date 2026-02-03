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
        Schema::table('users', function (Blueprint $table) {
            $table->index(['latitude', 'longitude']);
            $table->index('location_id');
            $table->index('visibility');
            $table->index('is_profile_completed');
            $table->index('total_matches');
            $table->index('gender');
            $table->index('last_login');
        });

        Schema::table('user_sport', function (Blueprint $table) {
            $table->index(['user_id', 'sport_id']);
        });

        Schema::table('user_sport_scores', function (Blueprint $table) {
            $table->index('score_value');
            $table->index('score_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['location_id']);
            $table->dropIndex(['visibility']);
            $table->dropIndex(['is_profile_completed']);
            $table->dropIndex(['total_matches']);
            $table->dropIndex(['gender']);
            $table->dropIndex(['last_login']);
        });

        Schema::table('user_sport', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'sport_id']);
        });

        Schema::table('user_sport_scores', function (Blueprint $table) {
            $table->dropIndex(['score_value']);
            $table->dropIndex(['score_type']);
        });
    }
};
