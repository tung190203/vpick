<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('club_activity_participants', function (Blueprint $table) {
            if (!Schema::hasColumn('club_activity_participants', 'is_absent')) {
                $table->boolean('is_absent')->default(false)->after('checked_in_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('club_activity_participants', function (Blueprint $table) {
            if (Schema::hasColumn('club_activity_participants', 'is_absent')) {
                $table->dropColumn('is_absent');
            }
        });
    }
};

