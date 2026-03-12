<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mini_tournaments', function (Blueprint $table) {
            // Toggle có thu phí hay không
            $table->boolean('has_fee')->default(false)->after('format')
                ->comment('True: có thu phí, False: miễn phí');
            
            // Chia tiền sân tự động (tổng tiền / số người)
            $table->boolean('auto_split_court_fee')->default(false)->after('has_fee')
                ->comment('True: chia đều theo số người, False: tiền cố định/người');
            
            // Ghi chú chi phí
            $table->text('payment_note')->nullable()->after('auto_split_court_fee')
                ->comment('Ghi chú về chi phí');
            
            // URL ảnh QR thanh toán
            $table->string('qr_code_image')->nullable()->after('payment_note')
                ->comment('URL ảnh QR thanh toán');
            
            // Tài khoản thanh toán (FK -> club_wallets)
            $table->unsignedBigInteger('payment_account_id')->nullable()->after('qr_code_image')
                ->comment('Tài khoản thanh toán QR');
                
            $table->foreign('payment_account_id')
                ->references('id')
                ->on('club_wallets')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('mini_tournaments', function (Blueprint $table) {
            $table->dropForeign(['payment_account_id']);
            $table->dropColumn([
                'has_fee',
                'auto_split_court_fee', 
                'payment_note',
                'qr_code_image',
                'payment_account_id'
            ]);
        });
    }
};
