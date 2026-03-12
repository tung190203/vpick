<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Kiểm tra và drop cột start_time nếu tồn tại (từ migration trước)
        if (Schema::hasColumn('mini_tournaments', 'start_time') && Schema::hasColumn('mini_tournaments', 'starts_at')) {
            Schema::table('mini_tournaments', function (Blueprint $table) {
                $table->dropColumn('start_time');
            });
        }

        // Đổi tên columns chỉ khi cột nguồn tồn tại
        if (Schema::hasColumn('mini_tournaments', 'starts_at')) {
            DB::statement('ALTER TABLE mini_tournaments CHANGE COLUMN starts_at start_time DATETIME NULL');
        }
        if (Schema::hasColumn('mini_tournaments', 'duration_minutes')) {
            DB::statement('ALTER TABLE mini_tournaments CHANGE COLUMN duration_minutes duration INT UNSIGNED NULL');
        }
        if (Schema::hasColumn('mini_tournaments', 'auto_split_court_fee')) {
            DB::statement('ALTER TABLE mini_tournaments CHANGE COLUMN auto_split_court_fee auto_split_fee TINYINT(1) DEFAULT 0');
        }
        if (Schema::hasColumn('mini_tournaments', 'qr_code_image')) {
            DB::statement('ALTER TABLE mini_tournaments CHANGE COLUMN qr_code_image qr_code_url VARCHAR(255) NULL');
        }
        if (Schema::hasColumn('mini_tournaments', 'payment_note')) {
            DB::statement('ALTER TABLE mini_tournaments CHANGE COLUMN payment_note fee_description TEXT NULL');
        }
        if (Schema::hasColumn('mini_tournaments', 'games_per_set')) {
            DB::statement('ALTER TABLE mini_tournaments CHANGE COLUMN games_per_set base_points INT UNSIGNED DEFAULT 11');
        }

        // Thêm các columns mới chỉ khi chưa tồn tại
        Schema::table('mini_tournaments', function (Blueprint $table) {
            if (!Schema::hasColumn('mini_tournaments', 'apply_rule')) {
                $table->boolean('apply_rule')->default(false)->after('format')
                    ->comment('Áp dụng luật thi đấu');
            }
            if (!Schema::hasColumn('mini_tournaments', 'allow_cancellation')) {
                $table->boolean('allow_cancellation')->default(true)->after('apply_rule')
                    ->comment('Cho phép hủy kèo');
            }
            if (!Schema::hasColumn('mini_tournaments', 'cancellation_duration')) {
                $table->integer('cancellation_duration')->nullable()->after('allow_cancellation')
                    ->comment('Thời gian hủy trước (phút)');
            }
            if (!Schema::hasColumn('mini_tournaments', 'end_time')) {
                $table->datetime('end_time')->nullable()->after('start_time')
                    ->comment('Thời gian kết thúc');
            }
            // Thêm các fields mới theo yêu cầu API
            if (!Schema::hasColumn('mini_tournaments', 'auto_approve')) {
                $table->boolean('auto_approve')->default(false)->after('allow_cancellation')
                    ->comment('Tự động duyệt thành viên');
            }
            if (!Schema::hasColumn('mini_tournaments', 'allow_participant_add_friends')) {
                $table->boolean('allow_participant_add_friends')->default(false)->after('auto_approve')
                    ->comment('Cho phép người tham gia mời bạn');
            }
            if (!Schema::hasColumn('mini_tournaments', 'set_number')) {
                $table->unsignedInteger('set_number')->default(3)->after('base_points')
                    ->comment('Số set thi đấu');
            }
            if (!Schema::hasColumn('mini_tournaments', 'points_difference')) {
                $table->unsignedInteger('points_difference')->default(2)->after('set_number')
                    ->comment('Cách biệt điểm để thắng');
            }
            if (!Schema::hasColumn('mini_tournaments', 'max_points')) {
                $table->unsignedInteger('max_points')->nullable()->after('points_difference')
                    ->comment('Điểm tối đa');
            }
        });

        // Xóa các columns không cần (nếu tồn tại)
        $columnsToDrop = [];
        if (Schema::hasColumn('mini_tournaments', 'match_type')) {
            $columnsToDrop[] = 'match_type';
        }
        if (Schema::hasColumn('mini_tournaments', 'fee') && !Schema::hasColumn('mini_tournaments', 'fee_amount')) {
            // Chỉ xóa fee cũ nếu chưa có fee_amount (tránh xóa nhầm)
        } else if (Schema::hasColumn('mini_tournaments', 'fee')) {
            $columnsToDrop[] = 'fee';
        }
        if (Schema::hasColumn('mini_tournaments', 'prize_pool')) {
            $columnsToDrop[] = 'prize_pool';
        }
        if (Schema::hasColumn('mini_tournaments', 'enable_dupr')) {
            $columnsToDrop[] = 'enable_dupr';
        }
        if (Schema::hasColumn('mini_tournaments', 'enable_vndupr')) {
            $columnsToDrop[] = 'enable_vndupr';
        }
        if (Schema::hasColumn('mini_tournaments', 'court_switch_points')) {
            $columnsToDrop[] = 'court_switch_points';
        }
        if (Schema::hasColumn('mini_tournaments', 'gender_policy')) {
            $columnsToDrop[] = 'gender_policy';
        }
        if (Schema::hasColumn('mini_tournaments', 'age_group')) {
            $columnsToDrop[] = 'age_group';
        }
        if (Schema::hasColumn('mini_tournaments', 'repeat_type')) {
            $columnsToDrop[] = 'repeat_type';
        }
        if (Schema::hasColumn('mini_tournaments', 'role_type')) {
            $columnsToDrop[] = 'role_type';
        }
        if (Schema::hasColumn('mini_tournaments', 'lock_cancellation')) {
            $columnsToDrop[] = 'lock_cancellation';
        }
        if (Schema::hasColumn('mini_tournaments', 'send_notification')) {
            $columnsToDrop[] = 'send_notification';
        }

        if (!empty($columnsToDrop)) {
            Schema::table('mini_tournaments', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    public function down(): void
    {
        // Thêm lại các columns đã xóa
        Schema::table('mini_tournaments', function (Blueprint $table) {
            $table->unsignedTinyInteger('match_type')->default(1)->nullable();
            $table->string('fee')->nullable();
            $table->unsignedInteger('prize_pool')->default(0);
            $table->boolean('enable_dupr')->default(false);
            $table->boolean('enable_vndupr')->default(false);
            $table->unsignedSmallInteger('court_switch_points')->default(1);
            $table->unsignedTinyInteger('gender_policy')->default(1);
            $table->unsignedTinyInteger('age_group')->default(1);
            $table->unsignedTinyInteger('repeat_type')->default(1);
            $table->unsignedTinyInteger('role_type')->default(2);
            $table->unsignedTinyInteger('lock_cancellation')->default(1);
            $table->boolean('send_notification')->default(false);
        });

        // Đổi tên ngược lại
        if (Schema::hasColumn('mini_tournaments', 'start_time')) {
            DB::statement('ALTER TABLE mini_tournaments CHANGE COLUMN start_time starts_at DATETIME NULL');
        }
        if (Schema::hasColumn('mini_tournaments', 'duration')) {
            DB::statement('ALTER TABLE mini_tournaments CHANGE COLUMN duration duration_minutes INT UNSIGNED NULL');
        }
        if (Schema::hasColumn('mini_tournaments', 'auto_split_fee')) {
            DB::statement('ALTER TABLE mini_tournaments CHANGE COLUMN auto_split_fee auto_split_court_fee TINYINT(1) DEFAULT 0');
        }
        if (Schema::hasColumn('mini_tournaments', 'qr_code_url')) {
            DB::statement('ALTER TABLE mini_tournaments CHANGE COLUMN qr_code_url qr_code_image VARCHAR(255) NULL');
        }
        if (Schema::hasColumn('mini_tournaments', 'fee_description')) {
            DB::statement('ALTER TABLE mini_tournaments CHANGE COLUMN fee_description payment_note TEXT NULL');
        }
        if (Schema::hasColumn('mini_tournaments', 'base_points')) {
            DB::statement('ALTER TABLE mini_tournaments CHANGE COLUMN base_points games_per_set INT UNSIGNED DEFAULT 11');
        }

        // Xóa các columns mới đã thêm
        Schema::table('mini_tournaments', function (Blueprint $table) {
            $table->dropColumn([
                'apply_rule',
                'allow_cancellation',
                'cancellation_duration',
                'end_time',
                'auto_approve',
                'allow_participant_add_friends',
                'set_number',
                'points_difference',
                'max_points',
            ]);
        });
    }
};
