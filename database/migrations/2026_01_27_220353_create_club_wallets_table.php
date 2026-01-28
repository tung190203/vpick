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
        Schema::create('club_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('club_id');
            $table->enum('type', ['main', 'fund', 'donation'])->default('main')->comment('Loại ví: main, fund, donation');
            $table->string('currency', 3)->default('VND');
            $table->string('qr_code_url')->nullable()->comment('Mã QR thanh toán');
            $table->timestamps();
            
            $table->foreign('club_id')->references('id')->on('clubs')->onDelete('cascade');
            $table->index(['club_id', 'type']);
            $table->unique(['club_id', 'type', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('club_wallets');
    }
};
