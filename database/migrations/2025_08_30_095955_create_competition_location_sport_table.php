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
        Schema::create('competition_location_sport', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('competition_location_id');
            $table->unsignedBigInteger('sport_id');
            $table->foreign('competition_location_id')->references('id')->on('competition_locations')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('competition_location_sport');
    }
};
