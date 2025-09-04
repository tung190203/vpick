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
        Schema::create('user_sport_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_sport_id');
            $table->string('score_type');
            $table->decimal('score_value', 8, 2)->default(0);
            $table->timestamps();
            $table->unique(['user_sport_id', 'score_type']); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sport_scores');
    }
};
