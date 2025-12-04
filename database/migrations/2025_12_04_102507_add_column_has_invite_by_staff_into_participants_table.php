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
        Schema::table('participants', function (Blueprint $table) {
            $table->boolean('is_invite_by_organizer')->default(false);
        });
        Schema::table('tournament_staff', function (Blueprint $table) {
            $table->boolean('is_invite_by_organizer')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('is_invite_by_organizer');
        });
        Schema::table('tournament_staff', function (Blueprint $table) {
            $table->dropColumn('is_invite_by_organizer');
        });
    }
};
