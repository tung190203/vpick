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
            $table->integer('set_number')->default(1)->after('max_rating');
            $table->integer('games_per_set')->default(11)->after('set_number');
            $table->integer('points_difference')->default(2)->after('games_per_set');
            $table->integer('max_points')->default(11)->after('points_difference');
            $table->integer('court_switch_points')->default(5)->after('max_points');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mini_tournaments', function (Blueprint $table) {
            $table->dropColumn(['set_number', 'games_per_set', 'points_difference', 'max_points', 'court_switch_points']);
        });
    }
};
