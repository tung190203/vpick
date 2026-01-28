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
        Schema::create('club_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_wallet_id');
            $table->enum('direction', ['in', 'out'])->comment('Hướng giao dịch: in = thu, out = chi');
            $table->decimal('amount', 15, 2);
            $table->enum('source_type', ['monthly_fee', 'fund_collection', 'expense', 'donation', 'adjustment'])->nullable()->comment('Nguồn giao dịch');
            $table->unsignedBigInteger('source_id')->nullable()->comment('ID của bảng nguồn (payment, contribution, expense)');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'qr_code', 'other'])->default('cash');
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->string('reference_code')->nullable()->comment('Mã tham chiếu giao dịch');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->comment('Người tạo giao dịch');
            $table->unsignedBigInteger('confirmed_by')->nullable()->comment('Người xác nhận');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            
            $table->foreign('club_wallet_id')->references('id')->on('club_wallets')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['club_wallet_id', 'status']);
            $table->index(['club_wallet_id', 'direction']);
            $table->index(['source_type', 'source_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_wallet_transactions');
    }
};
