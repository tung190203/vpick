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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('dupr_score', 8, 1)->default(0)->after('vndupr_score');
            $table->tinyInteger('gender')->nullable()->after('dupr_score');
            $table->date('date_of_birth')->nullable()->after('gender');
            $table->string('latitute')->nullable()->after('date_of_birth');
            $table->string('longitute')->nullable()->after('latitute');
            $table->string('address')->nullable()->after('longitute');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function(Blueprint $table) {
            $table->dropColumn([
                'dupr_score',
                'gender',
                'date_of_birth',
                'latitute',
                'longitute',
                'address'
            ]);
        });
    }
};
