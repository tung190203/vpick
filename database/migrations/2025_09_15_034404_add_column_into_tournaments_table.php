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
        Schema::table('tournaments', function (Blueprint $table) {
           $table->dropColumn(['level']);
           $table->unsignedBigInteger('sport_id')->nullable()->after('name');
           $table->string('poster')->nullable()->after('id');
           $table->timestamp('registration_open_at')->nullable()->after('start_date');
           $table->timestamp('registration_closed_at')->nullable()->after('registration_open_at');
           $table->timestamp('early_registration_deadline')->nullable()->after('registration_closed_at');
           $table->integer('duration')->after('early_registration_deadline')->comment('1 => 1 ngày, 7 => 1 tuần, 30,31 => 1 tháng');
           $table->integer('min_level')->after('duration');
           $table->integer('max_level')->after('min_level');
           $table->tinyInteger('age_group')->after('max_level');
           $table->tinyInteger('gender_policy')->after('age_group');
           $table->enum('participant',['team', 'user'])->after('gender_policy');
           $table->integer('max_team')->nullable()->after('participant')->comment('trường hợp chọn participant là team');
           $table->integer('player_per_team')->nullable()->after('max_team')->comment('trường hợp chọn participant là team');
           $table->integer('max_player')->nullable()->after('player_per_team')->comment('trường hợp chọn participant là user');
           $table->enum('fee',['free', 'pair'])->after('max_player')->comment('Phí tham gia: miễn phí, đóng theo cặp');
           $table->decimal('standard_fee_amount')->nullable()->after('fee')->comment('Phí tiêu chuẩn');
           $table->boolean('is_private')->default(false);
           $table->unsignedTinyInteger('status')->default(1)
                ->comment('1:Draft, 2:Open, 3:Closed, 4:Cancelled')->after('is_private');
           $table->boolean('auto_approve')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }
};
