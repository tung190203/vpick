<?php

namespace App\Console\Commands;

use App\Enums\ClubActivityStatus;
use App\Models\Club\ClubActivity;
use Illuminate\Console\Command;

class AutoCompleteActivitiesCommand extends Command
{
    protected $signature = 'activities:auto-complete';
    protected $description = 'Tự động cập nhật status: scheduled->ongoing (đang diễn ra), scheduled/ongoing->completed (đã qua end_time)';

    public function handle(): int
    {
        $now = now();
        $completed = 0;
        $ongoing = 0;

        // 1. scheduled -> ongoing (khi start_time <= now < end_time)
        $toOngoing = ClubActivity::where('status', ClubActivityStatus::Scheduled)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->get();

        foreach ($toOngoing as $activity) {
            $activity->update(['status' => ClubActivityStatus::Ongoing]);
            $ongoing++;
            $this->line("  → Ongoing: #{$activity->id} {$activity->title}");
        }

        // 2. scheduled/ongoing -> completed (khi end_time < now) - kể cả recurring
        $toComplete = ClubActivity::whereIn('status', [ClubActivityStatus::Scheduled, ClubActivityStatus::Ongoing])
            ->where('end_time', '<', $now)
            ->get();

        foreach ($toComplete as $activity) {
            $activity->update(['status' => ClubActivityStatus::Completed]);
            $completed++;
            $this->line("  ✓ Completed: #{$activity->id} {$activity->title}");
        }

        $this->info("Đã cập nhật: {$ongoing} ongoing, {$completed} completed.");

        return 0;
    }
}
