<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE club_activities CHANGE COLUMN penalty_percentage penalty_amount DECIMAL(10, 2) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE club_activities CHANGE COLUMN penalty_amount penalty_percentage DECIMAL(5, 2) NOT NULL DEFAULT 50');
    }
};
