<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\MiniTournament;
use App\Models\MiniTournamentUserNotification;
use App\Notifications\MiniTournamentReminder;
use Carbon\Carbon;

class SendMiniTournamentRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $now = Carbon::now();
        $reminderTime = $now->copy()->addMinutes(15);

        $tournaments = MiniTournament::whereBetween('starts_at', [$now, $reminderTime])->get();

        foreach ($tournaments as $tournament) {
            $subscriptions = MiniTournamentUserNotification::where('mini_tournament_id', $tournament->id)
                ->whereNull('reminded_at')
                ->get();

            foreach ($subscriptions as $sub) {
                $sub->user->notify(new MiniTournamentReminder($tournament));
                $sub->update(['reminded_at' => now()]);
            }
        }
    }
}
