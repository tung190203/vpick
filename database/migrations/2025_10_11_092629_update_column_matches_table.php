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
        Schema::table('matches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('participant1_id');
            $table->dropConstrainedForeignId('participant2_id');
            $table->unsignedBigInteger('home_team_id')->nullable()->after('round');
            $table->unsignedBigInteger('away_team_id')->nullable()->after('home_team_id');
            $table->unsignedBigInteger('leg')->default(1)->after('away_team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['home_team_id', 'away_team_id', 'leg']);
            $table->foreignId('participant1_id')->constrained('participants')->after('round');
            $table->foreignId('participant2_id')->constrained('participants')->after('participant1_id');
        });
    }
};
