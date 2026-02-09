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
        Schema::table('club_fund_collections', function (Blueprint $table) {
            $table->decimal('amount_per_member', 15, 2)->nullable()->after('target_amount')->comment('Số tiền mỗi thành viên phải đóng (nếu null thì dùng target_amount khi hiển thị)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_fund_collections', function (Blueprint $table) {
            $table->dropColumn('amount_per_member');
        });
    }
};
