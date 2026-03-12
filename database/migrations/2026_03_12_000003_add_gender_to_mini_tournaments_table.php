<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mini_tournaments', function (Blueprint $table) {
            $table->unsignedTinyInteger('gender')->nullable()->after('max_points')
                ->comment('Giới tính: 1=nam, 2=nữ, 3=không giới hạn');
        });
    }

    public function down(): void
    {
        Schema::table('mini_tournaments', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
