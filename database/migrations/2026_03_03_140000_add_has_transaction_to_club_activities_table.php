<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->boolean('has_transaction')->default(false)->after('has_collection');
        });

        // Đồng bộ: activity đã có has_collection = true thì has_transaction = true
        \DB::table('club_activities')->where('has_collection', true)->update(['has_transaction' => true]);
    }

    public function down(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->dropColumn('has_transaction');
        });
    }
};
