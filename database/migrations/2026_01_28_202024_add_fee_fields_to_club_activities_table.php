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
        Schema::table('club_activities', function (Blueprint $table) {
            $table->decimal('fee_amount', 10, 2)->default(0)->after('location')->comment('Phí tham gia sự kiện');
            $table->decimal('penalty_percentage', 5, 2)->default(50)->after('fee_amount')->comment('Phần trăm phạt khi rút sau 4 tiếng (mặc định 50%)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->dropColumn(['fee_amount', 'penalty_percentage']);
        });
    }
};
