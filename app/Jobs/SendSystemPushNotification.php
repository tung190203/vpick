<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Models\SystemNotification;
use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSystemPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SystemNotification $notification
    ) {}

    public function handle()
    {
        Log::info('Sending system notification', [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
        ]);

        // ✅ Gửi cho tất cả devices đang enabled
        $devices = DeviceToken::where('is_enabled', true)->get();
        
        if ($devices->isEmpty()) {
            Log::warning('No active devices found for system notification');
            $this->notification->update(['sent_at' => now()]);
            return;
        }

        Log::info("Sending to {$devices->count()} active devices");

        $firebase = app(FirebaseService::class);
        $successCount = 0;
        $failCount = 0;

        foreach ($devices as $device) {
            try {
                $result = $firebase->sendToDevice(
                    $device,
                    $this->notification->title,
                    $this->notification->body,
                    array_merge(
                        $this->notification->data ?? [],
                        [
                            'type' => 'SYSTEM_NOTIFICATION',
                            'notification_id' => $this->notification->id,
                        ]
                    )
                );

                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            } catch (\Throwable $e) {
                $failCount++;
                Log::error('Failed to send system notification to device', [
                    'device_id' => $device->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('System notification completed', [
            'notification_id' => $this->notification->id,
            'total_devices' => $devices->count(),
            'success' => $successCount,
            'failed' => $failCount,
        ]);

        // ✅ Đánh dấu đã gửi
        $this->notification->update([
            'sent_at' => now(),
        ]);
    }    
}