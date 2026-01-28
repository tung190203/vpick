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
        Schema::create('club_monthly_fee_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->unsignedBigInteger('club_monthly_fee_id');
            $table->unsignedBigInteger('user_id');
            $table->date('period')->comment('Tháng/năm phải đóng (YYYY-MM-01)');
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('wallet_transaction_id')->nullable()->comment('Link đến wallet transaction');
            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->foreign('club_monthly_fee_id')->references('id')->on('club_monthly_fees')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('wallet_transaction_id')->references('id')->on('club_wallet_transactions')->onDelete('set null');
            $table->unique(['club_id', 'user_id', 'period']);
            $table->index(['club_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_monthly_fee_payments');
    }
};
