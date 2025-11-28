<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vndupr_history', function (Blueprint $table) {
            // 1. Drop foreign key cũ
            $table->dropForeign(['match_id']);

            // 2. Sửa cột thành nullable
            $table->unsignedBigInteger('match_id')->nullable()->change();

            // 3. Add foreign key lại
            $table->foreign('match_id')
                ->references('id')
                ->on('matches')
                ->onDelete('cascade');

            // Thêm mini_match_id (cột mới)
            $table->foreignId('mini_match_id')
                ->nullable()
                ->constrained('mini_matches')
                ->onDelete('cascade');
                $table->dropColumn(['match_rating']);
        });
    }

    public function down(): void
    {
        Schema::table('vndupr_history', function (Blueprint $table) {
            $table->dropForeign(['match_id']);
            $table->unsignedBigInteger('match_id')->nullable(false)->change();
            $table->foreign('match_id')
                ->references('id')
                ->on('matches')
                ->onDelete('cascade');

            $table->dropForeign(['mini_match_id']);
            $table->dropColumn('mini_match_id');
            $table->decimal('match_rating', 4, 2);
        });
    }
};
