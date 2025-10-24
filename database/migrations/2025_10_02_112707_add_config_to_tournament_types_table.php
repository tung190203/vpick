<?php

use App\Models\TournamentType;
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
        Schema::table('tournament_types', function (Blueprint $table) {
            // Xóa cột cũ
            $table->dropColumn(['type', 'description']);
            
            // --- LOẠI THỂ THỨC ---
            $table->tinyInteger('format')->after('tournament_id')->comment('1: Mixed, 2: Elimination, 3: Round Robin');
            $table->unsignedTinyInteger('num_legs')->default(1)->comment('1 = 1 lượt, 2 = lượt đi và lượt về');
            
            // --- LUẬT THI ĐẤU (chung cho cả 3 thể thức) ---
            $table->json('match_rules')->after('format')->comment('Cấu hình luật thi đấu: {
                "sets_per_match": 1,
                "points_to_win_set": 11,
                "winning_rule": 1,
                "max_points": 11,
                "serve_change_interval": null
            }');         
            // --- THỂ LỆ TRẬN ĐẤU ---
            $table->text('rules')->nullable()->after('match_rules')
                ->comment('Thể lệ trận đấu dạng văn bản thuần');
            $table->string('rules_file_path')->nullable()->after('rules')
                ->comment('Đường dẫn file thể lệ trận đấu');
            
            // --- CẤU HÌNH ĐẶC BIỆT THEO TỪNG LOẠI (JSON) ---
            $table->json('format_specific_config')->nullable()->after('rules_file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tournament_types', function (Blueprint $table) {
            $table->dropColumn([
                'format',
                'match_rules',
                'rules',
                'rules_file_path',
                'format_specific_config'
            ]);
            
            // Khôi phục cột cũ
            $table->enum('type', ['single', 'double', 'mixed'])->after('tournament_id');
            $table->string('description')->nullable()->after('type');
        });
    }
};