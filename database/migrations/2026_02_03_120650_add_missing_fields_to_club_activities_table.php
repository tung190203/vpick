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
        Schema::table('club_activities', function (Blueprint $table) {
            $table->string('venue_address')->nullable()->after('location');
            $table->decimal('guest_fee', 10, 2)->nullable()->after('fee_amount');
            $table->dateTime('cancellation_deadline')->nullable()->after('end_time');
            $table->string('qr_code_url')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_activities', function (Blueprint $table) {
            $table->dropColumn(['venue_address', 'guest_fee', 'cancellation_deadline', 'qr_code_url']);
        });
    }
};
