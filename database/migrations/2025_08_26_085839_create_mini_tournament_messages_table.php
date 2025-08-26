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
        Schema::create('mini_tournament_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['text', 'image', 'voice', 'emoji', 'file'])->default('text');
            $table->text('content')->nullable()->comment('text, emoji, link, ...');
            $table->json('meta')->nullable()->comment('json for image, voice, file, ...');
            $table->timestamps();
        });        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mini_tournament_messages');
    }
};
