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
        Schema::table('competition_locations', function (Blueprint $table) {
            $table->string('address')->nullable()->after('name');
            $table->string('image')->nullable()->after('address');
            $table->string('phone')->nullable()->after('image');
            $table->time('opening_time')->nullable()->after('phone');
            $table->time('closing_time')->nullable()->after('opening_time');
            $table->text('note_booking')->nullable()->after('closing_time');
            $table->string('website')->nullable()->after('note_booking');
            $table->string('avatar_url')->nullable()->after('website');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('competition_locations', function (Blueprint $table) {
            $table->dropColumn([
                'address',
                'image',
                'phone',
                'opening_time',
                'closing_time',
                'note_booking',
                'website',
                'avatar_url'
            ]);
        });
    }
};
