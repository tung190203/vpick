<?php

namespace App\Console\Commands;

use App\Enums\ClubActivityStatus;
use App\Models\Club\ClubActivity;
use Illuminate\Console\Command;
use Carbon\Carbon;

class AutoCompleteActivities extends Command
{
    protected $signature = 'activities:auto-complete';
    protected $description = 'Tự động chuyển các hoạt động đã qua ngày sang trạng thái completed';
    public function handle()
    {
        $today = Carbon::now()->startOfDay();

        // Chỉ lấy activities không phải recurring HOẶC là recurring nhưng đã có occurrence tiếp theo
        $activities = ClubActivity::whereIn('status', [
            ClubActivityStatus::Scheduled,
            ClubActivityStatus::Ongoing
        ])
            ->whereNotNull('end_time')
            ->whereDate('end_time', '<', $today)
            ->get();

        $count = 0;

        foreach ($activities as $activity) {
            $activity->update(['status' => ClubActivityStatus::Completed]);
            $count++;

            $activityDate = $activity->end_time->format('d/m/Y');
            $this->info("✓ Activity #{$activity->id} '{$activity->title}' (ngày {$activityDate}) đã được đánh dấu completed");
        }

        if ($count > 0) {
            $this->info("Đã tự động complete {$count} hoạt động");
        } elseif ($count === 0) {
            $this->info('Không có hoạt động nào cần complete');
        }

        return 0;
    }
}
