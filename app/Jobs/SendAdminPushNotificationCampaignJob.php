<?php

namespace App\Jobs;

use App\Enums\AdminPushNotification\CampaignStatus;
use App\Models\AdminPushNotificationCampaign;
use App\Models\User;
use App\Notifications\AdminPushCampaignNotification;
use App\Services\Admin\AdminPushNotification\CampaignRecipientResolverFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAdminPushNotificationCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 minutes for large campaigns
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public int $campaignId) {}

    public function handle(): void
    {
        $campaign = AdminPushNotificationCampaign::find($this->campaignId);

        if (!$campaign) {
            Log::warning('SendAdminPushNotificationCampaignJob: campaign not found', [
                'campaign_id' => $this->campaignId,
            ]);
            return;
        }

        // Idempotency: atomic update status PROCESSING nếu đang SCHEDULED/PROCESSING/DRAFT
        $claimed = AdminPushNotificationCampaign::where('id', $campaign->id)
            ->whereIn('status', [
                CampaignStatus::SCHEDULED->value,
                CampaignStatus::DRAFT->value,
                CampaignStatus::PROCESSING->value,
            ])
            ->update(['status' => CampaignStatus::PROCESSING->value]);

        if ($claimed === 0) {
            Log::info('SendAdminPushNotificationCampaignJob: already processed', [
                'campaign_id' => $this->campaignId,
                'status' => $campaign->status->value,
            ]);
            return;
        }

        $campaign->refresh();
        Log::info('Starting push notification campaign', [
            'campaign_id' => $campaign->id,
            'recipient_type' => $campaign->recipient_type->value,
            'recipient_config' => $campaign->recipient_config,
            'estimated_count' => $campaign->estimated_recipient_count,
        ]);

        // Validate recipient_config trước khi xử lý
        $config = $campaign->recipient_config ?? [];
        $this->validateRecipientConfig($campaign->recipient_type, $config);

        $resolverData = CampaignRecipientResolverFactory::makeWithConfig($campaign);
        $query = $resolverData['resolver']->buildQuery($resolverData['config']);

        // Lấy danh sách user IDs đủ điều kiện
        $userIds = $query->pluck('users.id')->toArray();

        if (empty($userIds)) {
            Log::info('No users found for campaign', ['campaign_id' => $campaign->id]);
        }

        // Notify users để lưu vào bảng notifications + dispatch FCM qua listener (SendPushNotificationListener → SendPushJob → FirebaseService::sendToUser).
        // Lưu ý: Job KHÔNG gọi Firebase trực tiếp để tránh duplicate push — FCM chỉ được gửi qua NotificationSent event.
        $usersToNotify = User::whereIn('id', $userIds)->get();
        foreach ($usersToNotify as $user) {
            $user->notify(new AdminPushCampaignNotification($campaign));
        }

        Log::info('Users notified for campaign', [
            'campaign_id' => $campaign->id,
            'notified_count' => $usersToNotify->count(),
        ]);

        // Xác định final status: FAILED nếu không có user nào được notify, ngược lại SENT.
        $finalStatus = $usersToNotify->isEmpty()
            ? CampaignStatus::FAILED
            : CampaignStatus::SENT;

        $campaign->update([
            'status' => $finalStatus->value,
            'sent_at' => now(),
            'actual_recipient_count' => $usersToNotify->count(),
            'success_count' => $usersToNotify->count(),
            'failure_count' => 0,
            'error_message' => $usersToNotify->isEmpty()
                ? 'Không tìm thấy user đủ điều kiện cho campaign.'
                : null,
            'metadata' => array_merge($campaign->metadata ?? [], [
                'completed_at' => now()->toIsoString(),
            ]),
        ]);

        Log::info('Push notification campaign completed', [
            'campaign_id' => $campaign->id,
            'status' => $finalStatus->value,
            'actual_recipient_count' => $usersToNotify->count(),
        ]);
    }

    protected function validateRecipientConfig(\App\Enums\AdminPushNotification\RecipientType $recipientType, array $config): void
    {
        $requiredFields = match ($recipientType) {
            \App\Enums\AdminPushNotification\RecipientType::CLUB => ['club_id'],
            \App\Enums\AdminPushNotification\RecipientType::USERS => ['user_ids'],
            \App\Enums\AdminPushNotification\RecipientType::ACTIVITY => ['level'],
            default => [],
        };

        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || (is_array($config[$field]) && empty($config[$field]))) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            throw new \InvalidArgumentException(
                "Campaign config missing required fields for recipient_type {$recipientType->value}: " . implode(', ', $missingFields)
            );
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendAdminPushNotificationCampaignJob failed', [
            'campaign_id' => $this->campaignId,
            'error' => $exception->getMessage(),
        ]);

        $campaign = AdminPushNotificationCampaign::find($this->campaignId);
        if ($campaign) {
            $campaign->update([
                'status' => CampaignStatus::FAILED->value,
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}