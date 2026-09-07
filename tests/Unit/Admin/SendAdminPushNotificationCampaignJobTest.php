<?php

namespace Tests\Unit\Admin;

use App\Enums\AdminPushNotification\CampaignStatus;
use App\Enums\AdminPushNotification\RecipientType;
use App\Jobs\SendAdminPushNotificationCampaignJob;
use App\Models\AdminPushNotificationCampaign;
use App\Models\DeviceToken;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendAdminPushNotificationCampaignJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_is_idempotent_when_called_twice(): void
    {
        $user = User::factory()->create();
        DeviceToken::create([
            'user_id' => $user->id,
            'token' => 'token-1',
            'platform' => 'android',
            'is_enabled' => true,
            'last_seen_at' => now(),
        ]);

        $campaign = AdminPushNotificationCampaign::create([
            'title' => 'Test',
            'content' => 'Body',
            'action_type' => 'NONE',
            'recipient_type' => RecipientType::ALL,
            'recipient_config' => ['type' => 'ALL'],
            'send_type' => 'IMMEDIATE',
            'status' => CampaignStatus::SCHEDULED->value,
            'created_by' => $user->id,
        ]);

        // Mock FirebaseService: Job KHÔNG gọi trực tiếp nữa, FCM đi qua Notification flow →
        // SendPushNotificationListener → SendPushJob → FirebaseService::sendToUser.
        // Mock sendToUser để verify FCM vẫn được gọi đúng 1 lần / user qua listener,
        // và KHÔNG còn sendMulticast / sendToDevice trực tiếp từ Job.
        $mock = $this->mock(FirebaseService::class, function ($mock) {
            $mock->shouldReceive('sendToUser')->andReturn(null);
            // Đảm bảo Job không còn gọi các method trực tiếp:
            $mock->shouldNotReceive('sendMulticast');
            $mock->shouldNotReceive('sendToDevice');
            $mock->shouldNotReceive('sendToTopic');
        });

        $job = new SendAdminPushNotificationCampaignJob($campaign->id);

        // Lần 1: process
        $job->handle();
        $campaign->refresh();
        $firstStatus = $campaign->status;
        $firstSentAt = $campaign->sent_at;

        $this->assertSame(CampaignStatus::SENT->value, $firstStatus);

        // Lần 2: idempotent - không process lại
        $job2 = new SendAdminPushNotificationCampaignJob($campaign->id);
        $job2->handle();
        $campaign->refresh();

        // Status không thay đổi (vẫn SENT)
        $this->assertEquals($firstStatus, $campaign->status);
        $this->assertEquals($firstSentAt, $campaign->sent_at);
    }

    public function test_job_marks_failed_when_no_eligible_users(): void
    {
        $admin = User::factory()->create();
        $campaign = AdminPushNotificationCampaign::create([
            'title' => 'Test',
            'content' => 'Body',
            'action_type' => 'NONE',
            'recipient_type' => RecipientType::USERS,
            'recipient_config' => ['user_ids' => [999999]], // user không tồn tại
            'send_type' => 'IMMEDIATE',
            'status' => CampaignStatus::SCHEDULED->value,
            'created_by' => $admin->id,
        ]);

        $mock = $this->mock(FirebaseService::class, function ($mock) {
            $mock->shouldNotReceive('sendToUser');
            $mock->shouldNotReceive('sendMulticast');
            $mock->shouldNotReceive('sendToDevice');
        });

        $job = new SendAdminPushNotificationCampaignJob($campaign->id);
        $job->handle();

        $campaign->refresh();
        $this->assertSame(CampaignStatus::FAILED->value, $campaign->status);
        $this->assertSame(0, $campaign->actual_recipient_count);
    }
}
