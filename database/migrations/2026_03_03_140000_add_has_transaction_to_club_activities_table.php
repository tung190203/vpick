<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->boolean('has_transaction')->default(false)->after('creator_always_join');
        });

        $activityIdsWithCollection = DB::table('club_fund_collections')
            ->whereNotNull('club_activity_id')
            ->pluck('club_activity_id')
            ->unique();
        $activityIdsWithExpense = DB::table('club_expenses')
            ->whereNotNull('club_activity_id')
            ->pluck('club_activity_id')
            ->unique();
        $ids = $activityIdsWithCollection->merge($activityIdsWithExpense)->unique()->filter()->values()->all();
        if (!empty($ids)) {
            DB::table('club_activities')->whereIn('id', $ids)->update(['has_transaction' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->dropColumn('has_transaction');
        });
    }
};