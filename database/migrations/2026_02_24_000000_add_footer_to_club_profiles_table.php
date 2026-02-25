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
        Schema::table('club_profiles', function (Blueprint $table) {
            $table->text('footer')->nullable()->after('country')->comment('Thông tin footer: SĐT admin, liên hệ, etc.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_profiles', function (Blueprint $table) {
            $table->dropColumn('footer');
        });
    }
};
