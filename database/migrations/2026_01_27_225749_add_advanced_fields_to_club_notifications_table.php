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
        Schema::table('club_notifications', function (Blueprint $table) {
            $table->string('attachment_url')->nullable()->after('content');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal')->after('attachment_url');
            $table->enum('status', ['draft', 'scheduled', 'sent', 'cancelled'])->default('draft')->after('priority');
            $table->timestamp('scheduled_at')->nullable()->after('status');
            $table->timestamp('sent_at')->nullable()->after('scheduled_at');
            
            $table->index(['status']);
            $table->index(['scheduled_at']);
            $table->index(['sent_at']);
            $table->index(['club_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('club_notifications', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['scheduled_at']);
            $table->dropIndex(['sent_at']);
            $table->dropIndex(['club_id', 'status']);
            $table->dropColumn(['attachment_url', 'priority', 'status', 'scheduled_at', 'sent_at']);
        });
    }
};
