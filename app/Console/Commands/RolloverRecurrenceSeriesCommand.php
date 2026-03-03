<?php

namespace App\Console\Commands;

use App\Services\Club\ClubActivityService;
use Illuminate\Console\Command;

class RolloverRecurrenceSeriesCommand extends Command
{
    protected $signature = 'activities:rollover-recurrence
                            {--dry-run : Only report what would be created}';

    protected $description = 'Create next period occurrences for recurring activity series that have passed their period end (weekly: next month, monthly: next 3 months, quarterly: next year, yearly: next 2 years)';

    public function handle(ClubActivityService $activityService): int
    {
        if ($this->option('dry-run')) {
            $this->info('Dry run – no changes will be made.');
        }

        $created = $activityService->rolloverRecurrenceSeries();

        $this->info("Rollover complete. Created {$created} new occurrence(s).");

        return self::SUCCESS;
    }
}
