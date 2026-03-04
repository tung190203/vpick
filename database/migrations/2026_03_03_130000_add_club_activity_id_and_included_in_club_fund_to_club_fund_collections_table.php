<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_fund_collections', function (Blueprint $table) {
            $table->foreignId('club_activity_id')->nullable()->after('club_id')
                ->constrained('club_activities')->nullOnDelete();
            $table->boolean('included_in_club_fund')->default(true)->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('club_fund_collections', function (Blueprint $table) {
            $table->dropForeign(['club_activity_id']);
            $table->dropColumn('included_in_club_fund');
        });
    }
};
