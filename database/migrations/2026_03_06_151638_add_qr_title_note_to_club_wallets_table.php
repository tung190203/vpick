<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_wallets', function (Blueprint $table) {
            $table->text('qr_note')->nullable()->after('qr_code_url')->comment('Ghi chú mã QR thanh toán');
        });
    }

    public function down(): void
    {
        Schema::table('club_wallets', function (Blueprint $table) {
            $table->dropColumn('qr_note');
        });
    }
};
