<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->dropColumn('is_recurring');
        });

        DB::statement('ALTER TABLE club_activities MODIFY COLUMN recurring_schedule JSON NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE club_activities MODIFY COLUMN recurring_schedule VARCHAR(255) NULL');

        Schema::table('club_activities', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('type');
        });
    }
};
