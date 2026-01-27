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
        Schema::create('club_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->string('title');
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('wallet_transaction_id')->nullable()->comment('Link đến wallet transaction (direction=out)');
            $table->unsignedBigInteger('spent_by')->nullable()->comment('Người chi');
            $table->timestamp('spent_at')->nullable();
            $table->text('note')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->foreign('spent_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('wallet_transaction_id')->references('id')->on('club_wallet_transactions')->onDelete('set null');
            $table->index(['club_id', 'spent_at']);
            $table->index('spent_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_expenses');
    }
};
