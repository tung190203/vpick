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
        Schema::create('pool_advancement_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('group_id')->nullable()->constrained()->onDelete('cascade');
            $table->integer('rank');
            $table->foreignId('next_match_id')->constrained('matches')->onDelete('cascade');
            $table->string('next_position');
            $table->timestamps();
            
            $table->index(['tournament_type_id', 'group_id', 'rank']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_advancement_rules');
    }
};
