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
        Schema::create('mini_tournament_user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();

            $table->unique(['mini_tournament_id', 'user_id'], 'mini_tour_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mini_tournament_user_notifications');
    }
};
