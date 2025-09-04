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
        Schema::table('mini_matches', function (Blueprint $table) {
            $table->unsignedBigInteger('yard_number')->nullable()->after('participant2_confirm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mini_matches', function (Blueprint $table) {
            $table->dropColumn('yard_number');
        });
    }
};
