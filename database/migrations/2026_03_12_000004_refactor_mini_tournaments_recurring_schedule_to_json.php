<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('mini_tournaments', 'recurring_schedule')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE mini_tournaments MODIFY COLUMN recurring_schedule JSON NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement("ALTER TABLE mini_tournaments ALTER COLUMN recurring_schedule TYPE jsonb USING NULLIF(recurring_schedule, '')::jsonb");
        }

        $rows = DB::table('mini_tournaments')
            ->whereNotNull('recurring_schedule')
            ->select(['id', 'recurring_schedule'])
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $raw = $row->recurring_schedule;
            if (!is_string($raw)) {
                continue;
            }

            $trimmed = trim($raw);
            if ($trimmed === '' || str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
                continue;
            }

            $weekDays = [];
            $tokens = preg_split('/[^0-9a-zA-Z]+/', strtolower($trimmed), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            foreach ($tokens as $t) {
                if ($t === 'cn' || $t === 'sun' || $t === 'sunday') {
                    $weekDays[] = 0; // CN
                    continue;
                }

                if (!ctype_digit($t)) {
                    continue;
                }

                $n = (int) $t;

                // Legacy formats may represent Sunday as 0 or 1
                if ($n === 0 || $n === 1) {
                    $weekDays[] = 0;
                    continue;
                }

                // 2..7 (T2..T7) -> 1..6 (Carbon dayOfWeek)
                if ($n >= 2 && $n <= 7) {
                    $weekDays[] = $n - 1;
                }
            }

            $weekDays = array_values(array_unique(array_map('intval', $weekDays)));
            sort($weekDays);

            if (empty($weekDays)) {
                continue;
            }

            $schedule = [
                'period' => 'weekly',
                'week_days' => $weekDays,
                'recurring_date' => null,
            ];

            DB::table('mini_tournaments')
                ->where('id', $row->id)
                ->update(['recurring_schedule' => json_encode($schedule)]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('mini_tournaments', 'recurring_schedule')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE mini_tournaments MODIFY COLUMN recurring_schedule VARCHAR(255) NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE mini_tournaments ALTER COLUMN recurring_schedule TYPE varchar(255) USING recurring_schedule::text');
        }
    }
};

