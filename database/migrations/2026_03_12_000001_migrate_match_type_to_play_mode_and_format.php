<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate data from match_type to play_mode + format
        $miniTournaments = DB::table('mini_tournaments')->get();

        foreach ($miniTournaments as $tournament) {
            $playMode = null;
            $format = null;

            switch ($tournament->match_type) {
                case 1: // Friendly
                    $playMode = 1; // Vui vẻ
                    $format = null;
                    break;
                case 2: // Round Robin
                    $playMode = 2; // Thi đấu
                    $format = null;
                    break;
                case 3: // Single
                    $playMode = 2; // Thi đấu
                    $format = 1; // Đánh đơn
                    break;
                case 4: // Double
                    $playMode = 2; // Thi đấu
                    $format = 2; // Đánh đôi
                    break;
                case 5: // Training
                    $playMode = 3; // Luyện tập
                    $format = null;
                    break;
                case 6: // Lesson
                    $playMode = 3; // Luyện tập
                    $format = null;
                    break;
                case 7: // Meeting
                    $playMode = 3; // Luyện tập
                    $format = null;
                    break;
            }

            if ($playMode !== null) {
                DB::table('mini_tournaments')
                    ->where('id', $tournament->id)
                    ->update([
                        'play_mode' => $playMode,
                        'format' => $format,
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Rollback: set play_mode and format back to null
        DB::table('mini_tournaments')
            ->whereNotNull('play_mode')
            ->update([
                'play_mode' => null,
                'format' => null,
            ]);
    }
};
