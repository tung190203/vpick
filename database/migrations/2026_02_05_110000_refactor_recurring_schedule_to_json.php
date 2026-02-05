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
        Schema::table('club_activities', function (Blueprint $table) {
            // Drop is_recurring field
            $table->dropColumn('is_recurring');

            // Change recurring_schedule to JSON type
            $table->json('recurring_schedule')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            // Add back is_recurring
            $table->boolean('is_recurring')->default(false)->after('type');

            // Change recurring_schedule back to string
            $table->string('recurring_schedule')->nullable()->change();
        });
    }
};
