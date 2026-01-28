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
        Schema::create('club_activity_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_activity_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['invited', 'accepted', 'declined', 'attended', 'absent'])->default('invited');
            $table->timestamps();
            
            $table->foreign('club_activity_id')->references('id')->on('club_activities')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['club_activity_id', 'user_id']);
            $table->index(['club_activity_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_activity_participants');
    }
};
