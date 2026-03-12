<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mini_recurring_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_tournament_id')->constrained('mini_tournaments')->cascadeOnDelete();
            
            // Loại lặp: daily, weekly, biweekly, monthly
            $table->enum('repeat_type', ['daily', 'weekly', 'biweekly', 'monthly'])->default('weekly')
                ->comment('Loại lặp: daily=每一天, weekly=mỗi tuần, biweekly=2 tuần, monthly=mỗi tháng');
            
            // Các ngày trong tuần lặp (json array,VD: [1,3,5] cho T2,T4,T6)
            $table->json('repeat_days')->nullable()
                ->comment('Các ngày trong tuần lặp [1-7]');
            
            // Thời gian trong ngày (VD: "09:00")
            $table->time('time')->nullable()
                ->comment('Thời gian trong ngày');
            
            // Ngày bắt đầu và kết thúc lịch lặp
            $table->date('start_date')->nullable()
                ->comment('Ngày bắt đầu lặp');
            $table->date('end_date')->nullable()
                ->comment('Ngày kết thúc lặp (null = vô thời hạn)');
            
            $table->timestamps();
            
            // Index
            $table->index('mini_tournament_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mini_recurring_schedules');
    }
};
