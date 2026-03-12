<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mini_tournaments', function (Blueprint $table) {
            $table->unsignedTinyInteger('play_mode')->nullable()->after('match_type')
                ->comment('1:Vui vẻ, 2:Thi đấu, 3:Luyện tập');
            $table->unsignedTinyInteger('format')->nullable()->after('play_mode')
                ->comment('1:Đánh đơn, 2:Đánh đôi, 3:Đôi nam, 4:Đôi nữ, 5:Mixed');
        });
    }

    public function down(): void
    {
        Schema::table('mini_tournaments', function (Blueprint $table) {
            $table->dropColumn(['play_mode', 'format']);
        });
    }
};
