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
        Schema::table('club_activity_participants', function (Blueprint $table) {
            $table->decimal('sport_score', 10, 2)->nullable()->after('status');
            $table->decimal('vndupr_score', 10, 2)->nullable()->after('sport_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_activity_participants', function (Blueprint $table) {
            $table->dropColumn(['sport_score', 'vndupr_score']);
        });
    }
};
