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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tournament_id');
            $table->string('round')->nullable();
            $table->unsignedBigInteger('player1_id');
            $table->unsignedBigInteger('player2_id');
            $table->string('score')->nullable();
            $table->enum('result', ['player1_win', 'player2_win', 'forfeit'])->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->boolean('qr_confirmed')->default(false);
            $table->unsignedBigInteger('referee_id')->nullable();
            $table->enum('status', ['pending', 'completed', 'disputed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
