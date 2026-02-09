<?php

namespace App\Console\Commands;

use App\Enums\ClubNotificationStatus;
use App\Models\Club\ClubNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledClubNotifications extends Command
{
    protected $signature = 'clubs:send-scheduled-notifications';
    protected $description = 'Send scheduled club notifications that are due';

    public function handle()
    {
        $notifications = ClubNotification::where('status', ClubNotificationStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->whereNull('sent_at')
            ->with('club')
            ->get();

        if ($notifications->isEmpty()) {
            $this->info('No scheduled notifications to send.');
            return 0;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($notifications as $notification) {
            try {
                $this->sendScheduledNotification($notification);
                $successCount++;
                
                $this->info("✓ Sent notification #{$notification->id}: {$notification->title}");
                
                Log::info('Scheduled club notification sent', [
                    'notification_id' => $notification->id,
                    'club_id' => $notification->club_id,
                    'title' => $notification->title,
                    'scheduled_at' => $notification->scheduled_at,
                ]);
            } catch (\Exception $e) {
                $failCount++;
                
                $this->error("✗ Failed to send notification #{$notification->id}: {$e->getMessage()}");
                
                Log::error('Failed to send scheduled club notification', [
                    'notification_id' => $notification->id,
                    'club_id' => $notification->club_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("\n" . str_repeat('=', 50));
        $this->info("Total: {$notifications->count()} | Success: {$successCount} | Failed: {$failCount}");
        $this->info(str_repeat('=', 50));

        return $failCount > 0 ? 1 : 0;
    }

    private function sendScheduledNotification(ClubNotification $notification): void
    {
        // Update status to Sent
        $notification->update([
            'status' => ClubNotificationStatus::Sent,
            'sent_at' => now(),
        ]);

        // Create recipients if not specified
        if ($notification->recipients()->count() === 0) {
            $club = $notification->club;
            
            if (!$club) {
                throw new \Exception("Club not found for notification #{$notification->id}");
            }

            $allMembers = $club->activeMembers()->pluck('user_id');
            
            if ($allMembers->isEmpty()) {
                Log::warning('No active members found for club', [
                    'club_id' => $club->id,
                    'notification_id' => $notification->id,
                ]);
            }

            foreach ($allMembers as $memberUserId) {
                $notification->recipients()->create([
                    'user_id' => $memberUserId,
                    'is_read' => false,
                ]);
            }
        }
    }
}
