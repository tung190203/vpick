<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('latitute');
            $table->decimal('longitude', 10, 7)->nullable()->after('longitute');
        });
    
        // copy data
        DB::statement('UPDATE users SET latitude = latitute, longitude = longitute');
    
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['latitute', 'longitute']);
        });
    }
    
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('latitute', 10, 7)->nullable()->after('latitude');
            $table->decimal('longitute', 10, 7)->nullable()->after('longitude');
        });
    
        DB::statement('UPDATE users SET latitute = latitude, longitute = longitude');
    
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
    
};
