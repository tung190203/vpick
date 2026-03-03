<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_expenses', function (Blueprint $table) {
            $table->foreignId('club_activity_id')->nullable()->after('club_id')
                ->constrained('club_activities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('club_expenses', function (Blueprint $table) {
            $table->dropForeign(['club_activity_id']);
        });
    }
};
