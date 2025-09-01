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
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('ID của người theo dõi');
            $table->unsignedBigInteger('followable_id')->comment('ID của đối tượng theo dõi');
            $table->string('followable_type')->comment('loại đối tượng theo dõi, ví dụ: User, Team, Topic');
            $table->timestamps();
            $table->unique(['user_id', 'followable_id', 'followable_type'], 'unique_follow');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
