<?php

namespace App\Console\Commands;

use App\Models\MiniTournament;
use App\Services\MiniTournamentService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RolloverMiniTournamentRecurrenceCommand extends Command
{
    protected $signature = 'mini-tournaments:rollover-recurrence
                            {--dry-run : Only report what would be created}';

    protected $description = 'Create next period occurrences for recurring mini tournament series';

    public function handle(MiniTournamentService $tournamentService): int
    {
        if ($this->option('dry-run')) {
            $this->info('Dry run – no changes will be made.');
        }

        // Get all recurring tournaments that need new occurrences
        $recurringTournaments = MiniTournament::whereNotNull('recurring_schedule')
            ->whereNotNull('recurrence_series_id')
            ->whereNull('recurrence_series_cancelled_at')
            ->get();

        $created = 0;

        foreach ($recurringTournaments as $tournament) {
            // Check if we need to create new occurrences
            $nextOccurrence = $tournament->calculateNextOccurrence();
            
            if ($nextOccurrence) {
                if (!$this->option('dry-run')) {
                    // Use reflection to call private method
                    $reflection = new \ReflectionClass($tournamentService);
                    $method = $reflection->getMethod('createNextOccurrence');
                    $method->setAccessible(true);
                    $method->invoke($tournamentService, $tournament, $nextOccurrence, 1, $tournament->recurrence_series_id);
                }
                
                $created++;
                $this->line("Created occurrence for tournament {$tournament->id} at {$nextOccurrence->toDateTimeString()}");
            }
        }

        $this->info("Rollover complete. Created {$created} new occurrence(s).");

        return self::SUCCESS;
    }
}
