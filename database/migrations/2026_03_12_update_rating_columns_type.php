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
            // Change min_rating and max_rating from decimal(3,1) to decimal(5,2)
            // This allows values up to 999.99 instead of 99.9
            $table->decimal('min_rating', 5, 2)->nullable()->change();
            $table->decimal('max_rating', 5, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mini_tournaments', function (Blueprint $table) {
            // Revert back to original decimal(3,1)
            $table->decimal('min_rating', 3, 1)->nullable()->change();
            $table->decimal('max_rating', 3, 1)->nullable()->change();
        });
    }
};
