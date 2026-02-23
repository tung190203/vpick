<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('club_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->unsignedBigInteger('user_id')->comment('Người báo cáo');
            $table->string('reason_type', 50)->comment('Lý do: spam, inappropriate, fraud, harassment, other');
            $table->text('description')->nullable()->comment('Mô tả chi tiết');
            $table->string('status', 20)->default('pending')->comment('pending, reviewed, resolved, dismissed');
            $table->unsignedBigInteger('reviewed_by')->nullable()->comment('Admin xử lý');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_note')->nullable()->comment('Ghi chú của admin');
            $table->timestamps();

            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['club_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('club_reports');
    }
};
