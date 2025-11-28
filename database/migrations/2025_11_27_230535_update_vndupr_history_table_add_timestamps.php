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
        Schema::table('vndupr_history', function (Blueprint $table) {
            if (Schema::hasColumn('vndupr_history', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vndupr_history', function (Blueprint $table) {
            $table->timestamp('updated_at')->useCurrent();
        });
    }
};
