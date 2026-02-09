<?php

namespace App\Console;

use App\Jobs\SendMiniTournamentRemindersJob;
use App\Models\DeviceToken;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new SendMiniTournamentRemindersJob())->everyMinute();
        $schedule->command('system:send-notifications')->everyMinute();
        $schedule->call(function () {
            DeviceToken::where('last_seen_at', '<', now()->subDays(60))->delete();
        })->daily();

        $schedule->command('activities:auto-complete')->everyFiveMinutes();

        $schedule->command('activities:generate-recurring --days-ahead=30')->hourly();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
