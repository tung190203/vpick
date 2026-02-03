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
        Schema::table('club_activity_participants', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('vndupr_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_activity_participants', function (Blueprint $table) {
            $table->dropColumn('checked_in_at');
        });
    }
};
