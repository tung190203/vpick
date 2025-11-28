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
            $table->decimal('score_before', 8, 2)->change();
            $table->decimal('score_after', 8, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vndupr_history', function (Blueprint $table) {
            $table->integer('score_before')->change();
            $table->integer('score_after')->change();
        });
    }
};
