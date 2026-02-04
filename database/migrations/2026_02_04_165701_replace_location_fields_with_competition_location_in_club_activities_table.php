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
            // Add competition_location_id
            $table->unsignedBigInteger('competition_location_id')->nullable()->after('mini_tournament_id');
            $table->foreign('competition_location_id')->references('id')->on('competition_locations')->onDelete('set null');

            // Drop old location fields
            $table->dropColumn(['location', 'venue_address']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            // Restore old location fields
            $table->string('location')->nullable()->after('end_time');
            $table->string('venue_address')->nullable()->after('location');

            // Drop competition_location_id
            $table->dropForeign(['competition_location_id']);
            $table->dropColumn('competition_location_id');
        });
    }
};
