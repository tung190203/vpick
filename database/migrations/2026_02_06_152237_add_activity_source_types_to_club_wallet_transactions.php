<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `club_wallet_transactions` MODIFY COLUMN `source_type` ENUM('monthly_fee', 'fund_collection', 'expense', 'donation', 'adjustment', 'activity', 'activity_penalty') NULL COMMENT 'Nguồn giao dịch'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `club_wallet_transactions` MODIFY COLUMN `source_type` ENUM('monthly_fee', 'fund_collection', 'expense', 'donation', 'adjustment') NULL COMMENT 'Nguồn giao dịch'");
    }
};
