<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // Drop tables if they exist
        Schema::dropIfExists('mini_match_results');
        Schema::dropIfExists('mini_matches');
        Schema::dropIfExists('mini_participants');
        Schema::dropIfExists('mini_tournaments');
        Schema::dropIfExists('mini_team_members');
        Schema::dropIfExists('mini_teams');
        Schema::dropIfExists('competition_locations');
        Schema::dropIfExists('competition_location_yards');
        Schema::dropIfExists('sports');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        // Sports
        Schema::create('sports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        // Mini Tournaments
        Schema::create('mini_tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('poster')->nullable();

            // Liên kết
            $table->foreignId('sport_id')->constrained('sports')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            // Thông tin hiển thị
            $table->string('name');                  // Tên trận đấu
            $table->text('description')->nullable(); // Ghi chú

            // Loại hình (Giao hữu, Vòng tròn, Đánh đơn, Đánh đôi, Tập luyện, Buổi học, Họp mặt...)
            $table->unsignedTinyInteger('match_type')->default(1)
                ->comment('1:Friendly, 2:Round Robin, 3:Single, 4:Double, 5:Training, 6:Lesson, 7:Meeting');

            // Thời gian & địa điểm
            $table->dateTime('starts_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedBigInteger('competition_location_id')->nullable()
                ->comment('ID của địa điểm trong bảng locations, nếu có');

            // Quyền riêng tư (public/private)
            $table->boolean('is_private')->default(false);

            // Phí tham gia
            $table->unsignedInteger('fee_amount')->default(0); // lưu integer (VND) cho dễ query/sort

            // Số người chơi
            $table->unsignedSmallInteger('max_players')->nullable();

            // DUPR
            $table->boolean('enable_dupr')->default(false);
            $table->boolean('enable_vndupr')->default(false);

            // Trình độ
            $table->decimal('min_rating', 3, 1)->nullable();
            $table->decimal('max_rating', 3, 1)->nullable();

            // Cài đặt nâng cao
            $table->unsignedTinyInteger('gender_policy')->default(1)->comment('1:nam, 2:nữ, 3:không giới hạn');
            $table->unsignedSmallInteger('min_age')->nullable();
            $table->unsignedSmallInteger('max_age')->nullable();

            // Lặp lại
            $table->unsignedTinyInteger('repeat_type')->default(1)
                ->comment('1:None, 2:Daily, 3:Weekly, 4:Monthly');

            // Vai trò
            $table->unsignedTinyInteger('role_type')->default(2)
                ->comment('1:Organizer, 2:Participant');

            // Khóa/cho phép hủy
            $table->boolean('lock_cancellation')->default(false);

            // Toggle
            $table->boolean('auto_approve')->default(true);
            $table->boolean('allow_participant_add_friends')->default(false);
            $table->boolean('send_notification')->default(false);

            // Trạng thái
            $table->unsignedTinyInteger('status')->default(1)
                ->comment('1:Draft, 2:Open, 3:Closed, 4:Cancelled');

            $table->timestamps();
        });

        // Mini Teams
        Schema::create('mini_teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('mini_tournament_id');
            $table->timestamps();
        });

        // Mini Team Members
        Schema::create('mini_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mini_team_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        // Competition Locations
        Schema::create('competition_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->timestamps();
        });
        // Competition Location Yards
        Schema::create('competition_location_yards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_location_id')->constrained('competition_locations')->cascadeOnDelete();
            $table->string('yard_number');
            $table->timestamps();
        });

        // Mini Participants
        Schema::create('mini_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_tournament_id')->constrained('mini_tournaments')->cascadeOnDelete();
            $table->enum('type', ['user', 'team']);
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('mini_teams')->cascadeOnDelete();
            $table->boolean('is_confirmed')->default(false);
            $table->timestamps();
        });

        // Mini Matches
        Schema::create('mini_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_tournament_id')->constrained('mini_tournaments')->cascadeOnDelete();
            $table->string('round')->nullable();
            $table->foreignId('participant1_id')->constrained('mini_participants')->cascadeOnDelete();
            $table->foreignId('participant2_id')->constrained('mini_participants')->cascadeOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedBigInteger('referee_id')->nullable();
            $table->unsignedBigInteger('participant_win_id')->nullable();
            $table->boolean('participant1_confirm')->default(false);
            $table->boolean('participant2_confirm')->default(false);
            $table->foreignId('competition_location_yards_id')->nullable()->constrained('competition_location_yards')->nullOnDelete()->cascadeOnUpdate();
            $table->enum('status', ['pending', 'completed', 'disputed'])->default('pending');
            $table->timestamps();
        });

        // Mini Match Results
        Schema::create('mini_match_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mini_match_id')->constrained('mini_matches')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('mini_participants')->cascadeOnDelete();
            $table->integer('score')->default(0);
            $table->boolean('won_set')->default(false);
            $table->unsignedTinyInteger('set_number')->default(1);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('mini_match_results');
        Schema::dropIfExists('mini_matches');
        Schema::dropIfExists('mini_participants');
        Schema::dropIfExists('mini_tournaments');
        Schema::dropIfExists('mini_team_members');
        Schema::dropIfExists('mini_teams');
        Schema::dropIfExists('competition_locations');
        Schema::dropIfExists('competition_location_yards');

        Schema::enableForeignKeyConstraints();
    }
};
