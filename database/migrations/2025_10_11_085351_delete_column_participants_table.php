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
            $table->dropForeign(['tournament_type_id']);
            $table->dropColumn('tournament_type_id');
            $table->dropColumn('type');
            $table->dropForeign(['team_id']);
            $table->dropColumn('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->foreignId('tournament_type_id')->constrained('tournament_types')->onDelete('cascade');
            $table->enum('type', ['user','team']);
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('cascade');
        });
    }
};
