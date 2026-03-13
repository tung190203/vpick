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
            $table->unsignedInteger('set_number')->nullable()->change();
            $table->unsignedInteger('base_points')->nullable()->change();
            $table->unsignedInteger('points_difference')->nullable()->change();
            $table->unsignedInteger('max_points')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mini_tournaments', function (Blueprint $table) {
            $table->unsignedInteger('set_number')->nullable(false)->change();
            $table->unsignedInteger('base_points')->nullable(false)->change();
            $table->unsignedInteger('points_difference')->nullable(false)->change();
            $table->unsignedInteger('max_points')->nullable(false)->change();
        });
    }
};
