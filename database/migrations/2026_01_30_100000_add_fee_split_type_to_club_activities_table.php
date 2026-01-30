<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Chia đều = tổng chi phí chia đều cho N người (fee_amount = total).
     * Cố định = mỗi người đóng X (fee_amount = per person).
     */
    public function up(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->string('fee_split_type', 20)->default('fixed')->after('penalty_percentage')
                ->comment('equal = chia đều (fee_amount là tổng), fixed = cố định (fee_amount là phí/người)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->dropColumn('fee_split_type');
        });
    }
};
