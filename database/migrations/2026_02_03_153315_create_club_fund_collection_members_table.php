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
        Schema::create('club_fund_collection_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_fund_collection_id')->constrained('club_fund_collections')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount_due', 15, 2)->default(0);
            $table->timestamps();

            // Unique constraint: một user chỉ được assign một lần trong một đợt thu
            $table->unique(['club_fund_collection_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_fund_collection_members');
    }
};
