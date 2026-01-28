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
        Schema::create('club_monthly_fees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('VND');
            $table->tinyInteger('due_day')->comment('Ngày trong tháng phải đóng phí (1-31)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->index(['club_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_monthly_fees');
    }
};
