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
        Schema::table('user_sport_scores', function(Blueprint $table) {
            $table->decimal('score_value',8, 3)->change();
        });
        Schema::table('vndupr_history', function(Blueprint $table) {
            $table->decimal('score_before',8, 3)->change();
            $table->decimal('score_after',8, 3)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_sport_scores', function(Blueprint $table) {
            $table->decimal('score_value', 8, 2)->change();
        });
        Schema::table('vndupr_history', function(Blueprint $table) {
            $table->decimal('score_before',8, 2)->change();
            $table->decimal('score_after',8,2)->change();
        });
    }
};
