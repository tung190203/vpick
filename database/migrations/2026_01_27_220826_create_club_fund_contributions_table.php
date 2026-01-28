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
        Schema::create('club_fund_contributions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_fund_collection_id');
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('wallet_transaction_id')->nullable()->comment('Link đến wallet transaction');
            $table->enum('status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->timestamps();
            
            $table->foreign('club_fund_collection_id')->references('id')->on('club_fund_collections')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('wallet_transaction_id')->references('id')->on('club_wallet_transactions')->onDelete('set null');
            $table->index(['club_fund_collection_id', 'status']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_fund_contributions');
    }
};
