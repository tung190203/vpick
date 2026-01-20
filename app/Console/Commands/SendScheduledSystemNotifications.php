<?php

namespace App\Console\Commands;

use App\Jobs\SendSystemPushNotification;
use App\Models\SystemNotification;
use Illuminate\Console\Command;

class SendScheduledSystemNotifications extends Command
{
    protected $signature = 'system:send-notifications';
    protected $description = 'Send scheduled system push notifications';

    public function handle()
    {
        $notifications = SystemNotification::whereNull('sent_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($notifications as $notification) {
            SendSystemPushNotification::dispatch($notification);
        }

        $this->info('System notifications dispatched.');
    }
}