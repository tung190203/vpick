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
        Schema::table('competition_location_yards', function (Blueprint $table) {
            $table->tinyInteger('yard_type')->nullable()->after('yard_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_location_yards', function (Blueprint $table) {
            $table->dropColumn('yard_type');
        });
    }
};
