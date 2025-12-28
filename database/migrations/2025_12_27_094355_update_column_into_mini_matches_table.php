<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('mini_matches', function (Blueprint $table) {
            $table->unsignedBigInteger('team1_id')->nullable()->after('mini_tournament_id');
            $table->unsignedBigInteger('team2_id')->nullable()->after('team1_id');
            $table->unsignedBigInteger('team_win_id')->nullable()->after('team2_id');
            $table->boolean('team1_confirm')->default(false);
            $table->boolean('team2_confirm')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('mini_matches', function (Blueprint $table) {
            $table->dropColumn([
                'team1_id',
                'team2_id',
                'team_win_id',
                'team1_confirm',
                'team2_confirm'
            ]);
        });
    }
};
