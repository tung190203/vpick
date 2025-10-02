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
        Schema::table('participants', function (Blueprint $table) {
            $table->unsignedBigInteger('tournament_id')->after('id');
            $table->dropForeign(['tournament_type_id']);
            $table->foreignId('tournament_type_id')->nullable()->change();
            $table->foreign('tournament_type_id')->references('id')->on('tournament_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('tournament_id');
            $table->dropForeign(['tournament_type_id']);
            $table->foreignId('tournament_type_id')->change();
            $table->foreign('tournament_type_id')->references('id')->on('tournament_types')->onDelete('cascade');
        });
    }
};
