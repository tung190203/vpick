<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `clubs` MODIFY COLUMN `status` ENUM('active', 'inactive', 'suspended', 'draft') NOT NULL DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `clubs` MODIFY COLUMN `status` ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active'");
    }
};
