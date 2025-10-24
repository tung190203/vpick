<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Schema::table('match_results', function (Blueprint $table) {
            $table->dropColumn('participant_id');
            $table->unsignedBigInteger('team_id')->after('match_id');
            $table->unsignedBigInteger('score')->nullable()->after('team_id');
            $table->unsignedBigInteger('set_number')->nullable()->after('score');
        });

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('match_results', function (Blueprint $table) {
            $table->unsignedBigInteger('participant_id')->after('match_id');
            $table->dropColumn('team_id');
            $table->dropColumn('score');
            $table->dropColumn('set_number');
        });
    }
};
