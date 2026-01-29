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
            $table->unsignedBigInteger('wallet_transaction_id')->nullable()->after('status');
            
            $table->foreign('wallet_transaction_id')->references('id')->on('club_wallet_transactions')->onDelete('set null');
            $table->index('wallet_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_activity_participants', function (Blueprint $table) {
            $table->dropForeign(['wallet_transaction_id']);
            $table->dropIndex(['wallet_transaction_id']);
            $table->dropColumn('wallet_transaction_id');
        });
    }
};
