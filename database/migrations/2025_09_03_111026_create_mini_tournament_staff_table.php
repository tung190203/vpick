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
        Schema::create('mini_tournament_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('role')->nullable();
            $table->timestamps();
        
            $table->unique(['mini_tournament_id', 'user_id', 'role']);
        });
    }        

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mini_tournament_staff');
    }
};
