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
        Schema::create('club_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->unsignedBigInteger('club_notification_type_id');
            $table->string('title');
            $table->text('content');
            $table->json('metadata')->nullable()->comment('Dữ liệu bổ sung (links, images, etc.)');
            $table->boolean('is_pinned')->default(false);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->foreign('club_notification_type_id')->references('id')->on('club_notification_types')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->index(['club_id', 'is_pinned']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_notifications');
    }
};
