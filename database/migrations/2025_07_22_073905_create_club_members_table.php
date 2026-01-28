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
        Schema::create('club_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['member', 'admin', 'manager', 'treasurer', 'secretary'])->default('member');
            $table->string('position')->nullable()->comment('Chức vụ trong CLB');
            $table->enum('status', ['pending', 'active', 'inactive', 'suspended'])->default('active')->comment('pending = join request chưa duyệt');
            
            // Join request fields (gộp từ club_join_requests)
            $table->text('message')->nullable()->comment('Lời nhắn từ join request');
            $table->unsignedBigInteger('reviewed_by')->nullable()->comment('Người duyệt join request');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable()->comment('Lý do từ chối');
            
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->text('notes')->nullable();
            
            $table->boolean('is_manager')->default(false)->comment('Deprecated: dùng role thay thế');
            $table->softDeletes();
            $table->timestamps();
            
            $table->unique(['user_id', 'club_id']);
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['club_id', 'status']);
            $table->index(['club_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_members');
    }
};
