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
            // Drop old venue_address column
            if (Schema::hasColumn('club_activities', 'venue_address')) {
                $table->dropColumn('venue_address');
            }

            // Add new location fields
            $table->string('address', 500)->nullable()->after('end_time');
            $table->decimal('latitude', 10, 8)->nullable()->after('address');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->dropColumn(['address', 'latitude', 'longitude']);

            // Re-add venue_address if needed
            $table->string('venue_address')->nullable()->after('location');
        });
    }
};
