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
        Schema::table('mini_tournaments', function (Blueprint $table) {
            $table->tinyInteger('age_group')->default(1)->comment('1: All Ages, 2: Youth, 3: Adult, 4: Senior'); 
            $table->dropColumn(['min_age', 'max_age']);
            $table->enum('fee', ['none','free', 'auto_split', 'per_person'])->default('none')->after('is_private');
            $table->unsignedBigInteger('prize_pool')->default(0)->after('fee_amount');
            $table->tinyInteger('lock_cancellation')->default(1)->change();
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mini_tournaments', function (Blueprint $table) {
            $table->unsignedSmallInteger('min_age')->nullable();
            $table->unsignedSmallInteger('max_age')->nullable();
            $table->dropColumn('age_group');
            $table->dropColumn('fee');
            $table->dropColumn('prize_pool');
            $table->boolean('lock_cancellation')->default(false)->change();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
