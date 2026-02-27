<?php

namespace App\Console\Commands;

use App\Models\Club\ClubActivity;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupRecurringActivities extends Command
{
    protected $signature = 'activities:cleanup-recurring';

    protected $description = 'Xoá các bản ghi hoạt động lặp lại bị trùng (giữ lại 1 bản cho mỗi cặp club_id/title/recurring_schedule/start_time)';

    public function handle(): int
    {
        $this->info('Đang tìm các hoạt động lặp lại bị trùng...');

        $duplicates = ClubActivity::select([
                'club_id',
                'title',
                'recurring_schedule',
                'start_time',
                DB::raw('COUNT(*) as cnt'),
                DB::raw('MIN(id) as keep_id'),
            ])
            ->whereNotNull('recurring_schedule')
            ->groupBy('club_id', 'title', 'recurring_schedule', 'start_time')
            ->having('cnt', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('Không tìm thấy bản ghi trùng nào.');
            return static::SUCCESS;
        }

        $totalDeleted = 0;

        foreach ($duplicates as $dup) {
            $idsToDelete = ClubActivity::where('club_id', $dup->club_id)
                ->where('title', $dup->title)
                ->where('recurring_schedule', $dup->recurring_schedule)
                ->where('start_time', $dup->start_time)
                ->where('id', '!=', $dup->keep_id)
                ->pluck('id')
                ->all();

            if (empty($idsToDelete)) {
                continue;
            }

            $deleted = ClubActivity::whereIn('id', $idsToDelete)->delete();
            $totalDeleted += $deleted;

            $this->info(sprintf(
                'Giữ lại activity #%d, xoá %d bản trùng (club_id=%d, title="%s", start_time=%s)',
                $dup->keep_id,
                $deleted,
                $dup->club_id,
                $dup->title,
                $dup->start_time
            ));
        }

        $this->info("Hoàn tất cleanup. Đã xoá tổng cộng {$totalDeleted} bản ghi.");

        return static::SUCCESS;
    }
}

