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
        Schema::table('club_fund_contributions', function (Blueprint $table) {
            $table->string('receipt_url')->nullable()->after('wallet_transaction_id');
            $table->text('note')->nullable()->after('receipt_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_fund_contributions', function (Blueprint $table) {
            $table->dropColumn(['receipt_url', 'note']);
        });
    }
};
