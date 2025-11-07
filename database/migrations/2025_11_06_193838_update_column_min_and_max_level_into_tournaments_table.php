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
        Schema::table('tournaments', function (Blueprint $table) {
            $table->integer('min_level')->nullable()->change();
            $table->integer('max_level')->nullable()->change();
            $table->decimal('standard_fee_amount', 11, 0)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournaments', function (Blueprint $table) {
            $table->integer('min_level')->change();
            $table->integer('max_level')->change();
            $table->decimal('standard_fee_amount')->nullable()->change();
        });
    }
};
