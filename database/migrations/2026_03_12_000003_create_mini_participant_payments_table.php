<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mini_participant_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_tournament_id')->constrained('mini_tournaments')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('mini_participants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            
            // Số tiền phải đóng
            $table->unsignedInteger('amount')->comment('Số tiền phải đóng');
            
            // Trạng thái: pending, paid, confirmed, rejected
            $table->enum('status', ['pending', 'paid', 'confirmed', 'rejected'])->default('pending')
                ->comment('pending: chờ thanh toán, paid: đã thanh toán (chờ xác nhận), confirmed: đã xác nhận, rejected: bị từ chối');
            
            // Ảnh biên lai (bắt buộc khi paid)
            $table->string('receipt_image')->nullable()->comment('URL ảnh biên lai thanh toán');
            
            // Ghi chú của thành viên (không bắt buộc)
            $table->text('note')->nullable()->comment('Ghi chú khi thanh toán');
            
            // Ghi chú của chủ kèo khi confirm/reject
            $table->text('admin_note')->nullable()->comment('Ghi chú của chủ kèo khi xác nhận/từ chối');
            
            // Thời gian
            $table->timestamp('paid_at')->nullable()->comment('Thời điểm thành viên nộp tiền');
            $table->timestamp('confirmed_at')->nullable()->comment('Thời điểm chủ kèo xác nhận');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete()->comment('Người xác nhận');
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['mini_tournament_id', 'participant_id'], 'unique_participant_payment');
            $table->index(['mini_tournament_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mini_participant_payments');
    }
};
