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
        Schema::table("users", function (Blueprint $table) {
            $table->boolean("is_anchor")->default(false)->after("last_login");
            $table->unsignedBigInteger('total_matches_has_anchor')->default(0)->after('is_anchor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("users", function (Blueprint $table) {
            $table->dropColumn(['is_anchor', 'total_matches_has_anchor']);
        });
    }
};
