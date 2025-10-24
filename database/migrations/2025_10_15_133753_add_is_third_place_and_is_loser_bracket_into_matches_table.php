<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Đảm bảo bảng matches dùng InnoDB
        DB::statement('ALTER TABLE matches ENGINE=InnoDB');

        Schema::table('matches', function (Blueprint $table) {
            $table->unsignedBigInteger('next_match_id')->nullable()->after('round');
            $table->enum('next_position', ['home', 'away'])->nullable()->after('next_match_id');

            $table->unsignedBigInteger('loser_next_match_id')->nullable()->after('next_position');
            $table->enum('loser_next_position', ['home', 'away'])->nullable()->after('loser_next_match_id');

            $table->boolean('is_loser_bracket')->default(false)->after('is_bye');
            $table->boolean('is_third_place')->default(false)->after('is_loser_bracket');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn([
                'next_match_id',
                'next_position',
                'loser_next_match_id',
                'loser_next_position',
                'is_loser_bracket',
                'is_third_place',
            ]);
        });
    }
};
