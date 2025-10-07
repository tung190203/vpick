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
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'email' hoặc 'phone'
            $table->string('identifier'); // địa chỉ email hoặc số điện thoại
            $table->string('otp', 10);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['type', 'identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_codes');
    }
};
