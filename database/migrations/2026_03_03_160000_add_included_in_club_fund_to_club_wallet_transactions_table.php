<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_wallet_transactions', function (Blueprint $table) {
            $table->boolean('included_in_club_fund')->default(true)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('club_wallet_transactions', function (Blueprint $table) {
            $table->dropColumn('included_in_club_fund');
        });
    }
};
