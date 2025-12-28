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
        Schema::table("mini_participants", function (Blueprint $table) {
            if (Schema::hasColumn('mini_participants', 'team_id')) {
                $table->dropForeign(['team_id']);
                $table->dropColumn('team_id');
            }
            if (Schema::hasColumn('mini_participants', 'type')) {
                $table->dropColumn('type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("mini_participants", function (Blueprint $table) {
            $table->enum('type', ['user', 'team'])->default('user')->after('mini_tournament_id');
            $table->foreignId('team_id')->nullable()->after('user_id')->constrained('mini_teams')->cascadeOnDelete();
        });
    }
};
