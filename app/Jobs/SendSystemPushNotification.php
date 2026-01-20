<?php

namespace App\Jobs;

use App\Models\SystemNotification;
use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSystemPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SystemNotification $notification
    ) {}

    public function handle()
    {
        app(FirebaseService::class)->sendToTopic(
            'system',
            $this->notification->title,
            $this->notification->body,
            $this->notification->data ?? [
                'type' => 'SYSTEM_NOTIFICATION'
            ]
        );
    
        $this->notification->update([
            'sent_at' => now(),
        ]);
    }    
}

