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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vndupr_score', 'tier', 'dupr_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('vndupr_score', 8, 1)->default(0)->after('avatar_url');
            $table->string('tier')->nullable()->after('vndupr_score');
            $table->decimal('dupr_score', 8, 1)->default(0)->after('vndupr_score');
        });
    }
};
