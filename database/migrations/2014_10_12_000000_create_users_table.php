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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('avatar_url')->nullable();
            $table->string('google_id')->nullable();
            $table->decimal('vndupr_score', 8, 1)->default(0);
            $table->string('tier')->nullable();
            $table->enum('role', ['player', 'referee', 'admin'])->default('player');
            $table->timestamp('email_verified_at')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->text('about')->nullable();
            $table->boolean('is_profile_completed')->default(false)
                ->comment('Xác định xem người dùng đã hoàn thành hồ sơ cá nhân hay chưa');
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
