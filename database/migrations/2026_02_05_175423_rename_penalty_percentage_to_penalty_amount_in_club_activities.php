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
        // First, change the data type
        Schema::table('club_activities', function (Blueprint $table) {
            $table->decimal('penalty_percentage', 10, 2)->default(0)->change();
        });

        // Then, rename the column
        Schema::table('club_activities', function (Blueprint $table) {
            $table->renameColumn('penalty_percentage', 'penalty_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename back first
        Schema::table('club_activities', function (Blueprint $table) {
            $table->renameColumn('penalty_amount', 'penalty_percentage');
        });

        // Then change back to old data type
        Schema::table('club_activities', function (Blueprint $table) {
            $table->decimal('penalty_percentage', 5, 2)->default(50)->change();
        });
    }
};
