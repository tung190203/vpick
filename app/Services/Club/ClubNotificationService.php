<?php

namespace App\Services\Club;

use App\Enums\ClubMemberRole;
use App\Enums\ClubNotificationPriority;
use App\Enums\ClubNotificationStatus;
use App\Jobs\SendPushJob;
use App\Models\Club\Club;
use App\Models\Club\ClubNotification;
use App\Models\Club\ClubNotificationRecipient;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use App\Notifications\ClubNotificationSentNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ClubNotificationService
{
    public function getNotifications(Club $club, int $userId, array $filters, bool $canManage): LengthAwarePaginator
    {
        $query = $club->notifications()->with(['type', 'creator', 'recipients.user']);

        if (!$canManage) {
            $query->where('status', ClubNotificationStatus::Sent)
                ->whereHas('recipients', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
        }

        if (isset($filters['is_pinned'])) {
            $query->where('is_pinned', $filters['is_pinned']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type_id'])) {
            $query->where('club_notification_type_id', $filters['type_id']);
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function createNotification(Club $club, array $data, int $creatorId, ?string $attachmentUrl = null): ClubNotification
    {
        return DB::transaction(function () use ($club, $data, $creatorId, $attachmentUrl) {
            $notification = ClubNotification::create([
                'club_id' => $club->id,
                'club_notification_type_id' => $data['club_notification_type_id'],
                'title' => $data['title'],
                'content' => $data['content'],
                'attachment_url' => $attachmentUrl,
                'priority' => $data['priority'] ?? ClubNotificationPriority::Normal,
                'status' => $data['status'] ?? ClubNotificationStatus::Draft,
                'metadata' => $data['metadata'] ?? null,
                'is_pinned' => $data['is_pinned'] ?? false,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'created_by' => $creatorId,
            ]);

            if (!empty($data['user_ids'])) {
                foreach ($data['user_ids'] as $recipientUserId) {
                    $notification->recipients()->create([
                        'user_id' => $recipientUserId,
                        'is_read' => false,
                    ]);
                }
            }

            $status = $data['status'] ?? ClubNotificationStatus::Draft;
            if ($status === ClubNotificationStatus::Sent && $notification->recipients()->count() > 0) {
                $this->dispatchUserNotifications($club, $notification);
            }

            return $notification;
        });
    }

    public function updateNotification(ClubNotification $notification, array $data, ?string $newAttachmentUrl = null): ClubNotification
    {
        if ($newAttachmentUrl) {
            if ($notification->attachment_url) {
                $oldPath = str_replace('/storage/', '', $notification->attachment_url);
                Storage::disk('public')->delete($oldPath);
            }
            $data['attachment_url'] = $newAttachmentUrl;
        }

        $notification->update($data);
        return $notification;
    }

    public function deleteNotification(ClubNotification $notification): void
    {
        if ($notification->attachment_url) {
            $oldPath = str_replace('/storage/', '', $notification->attachment_url);
            Storage::disk('public')->delete($oldPath);
        }

        $notification->delete();
    }

    public function sendNotification(ClubNotification $notification, Club $club): ClubNotification
    {
        if ($notification->status === ClubNotificationStatus::Sent) {
            throw new \Exception('Thông báo đã được gửi');
        }

        $notification->update([
            'status' => ClubNotificationStatus::Sent,
            'sent_at' => now(),
        ]);

        if ($notification->recipients()->count() === 0) {
            $allMembers = $club->activeMembers()->pluck('user_id');
            foreach ($allMembers as $memberUserId) {
                $notification->recipients()->firstOrCreate([
                    'user_id' => $memberUserId,
                ], [
                    'is_read' => false,
                ]);
            }
        }

        $this->dispatchUserNotifications($club, $notification);

        return $notification;
    }

    public function dispatchUserNotificationsForSentNotification(ClubNotification $notification): void
    {
        $club = $notification->club;
        if (!$club) {
            return;
        }
        $this->dispatchUserNotifications($club, $notification);
    }

    private function dispatchUserNotifications(Club $club, ClubNotification $notification): void
    {
        $recipientUserIds = $notification->recipients()->pluck('user_id')->unique();
        $users = User::whereIn('id', $recipientUserIds)->get();

        $title = $notification->title ?: 'Thông báo từ CLB';
        $body = $notification->content ?: $club->name;

        foreach ($users as $user) {
            $user->notify(new ClubNotificationSentNotification($club, $notification));
            SendPushJob::dispatch($user->id, $title, $body, [
                'type' => 'CLUB_NOTIFICATION',
                'club_id' => (string) $club->id,
                'club_notification_id' => (string) $notification->id,
            ]);
        }
    }

    public function markAsRead(ClubNotification $notification, int $userId, bool $canManage): void
    {
        $recipient = $notification->recipients()->where('user_id', $userId)->first();

        if (!$recipient) {
            if (!$canManage) {
                throw new \Exception('Bạn không có quyền đánh dấu đọc thông báo này');
            }

            $notification->recipients()->create([
                'user_id' => $userId,
                'is_read' => true,
                'read_at' => now(),
            ]);
        } else {
            $recipient->markAsRead();
        }

        $this->syncLaravelNotificationRead($notification->id, $userId);
    }

    /**
     * Đồng bộ: club notification mark-read → Laravel notifications table
     */
    public function syncLaravelNotificationRead(int $clubNotificationId, int $userId): void
    {
        DatabaseNotification::where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->where('type', ClubNotificationSentNotification::class)
            ->where('data->club_notification_id', $clubNotificationId)
            ->update(['read_at' => now()]);
    }

    /**
     * Đồng bộ: Laravel notification mark-read → club_notification_recipients
     */
    public function syncClubRecipientRead(int $clubNotificationId, int $userId): void
    {
        ClubNotificationRecipient::updateOrCreate(
            [
                'club_notification_id' => $clubNotificationId,
                'user_id' => $userId,
            ],
            ['is_read' => true, 'read_at' => now()]
        );
    }

    public function markAllAsRead(Club $club, int $userId, bool $canManage): string
    {
        return DB::transaction(function () use ($club, $userId, $canManage) {
            $recipients = 0;
            if ($canManage) {
                $notifications = $club->notifications()->get();

                foreach ($notifications as $notification) {
                    $recipient = $notification->recipients()->where('user_id', $userId)->first();

                    if (!$recipient) {
                        $notification->recipients()->create([
                            'user_id' => $userId,
                            'is_read' => true,
                            'read_at' => now(),
                        ]);
                    } else {
                        $recipient->markAsRead();
                    }
                }
            } else {
                $recipients = DB::table('club_notification_recipients')
                    ->join('club_notifications', 'club_notification_recipients.club_notification_id', '=', 'club_notifications.id')
                    ->where('club_notifications.club_id', $club->id)
                    ->where('club_notification_recipients.user_id', $userId)
                    ->where('club_notification_recipients.is_read', false)
                    ->update([
                        'club_notification_recipients.is_read' => true,
                        'club_notification_recipients.read_at' => now(),
                    ]);
            }

            $this->syncAllLaravelNotificationsForClub($club->id, $userId);

            return $canManage
                ? 'Đã đánh dấu đọc tất cả thông báo'
                : ($recipients > 0 ? "Đã đánh dấu đọc {$recipients} thông báo" : 'Không có thông báo chưa đọc');
        });
    }

    /**
     * Đồng bộ mark-all-read: cập nhật tất cả Laravel notifications của club cho user
     */
    private function syncAllLaravelNotificationsForClub(int $clubId, int $userId): void
    {
        DatabaseNotification::where('notifiable_type', User::class)
            ->where('notifiable_id', $userId)
            ->where('type', ClubNotificationSentNotification::class)
            ->where('data->club_id', $clubId)
            ->update(['read_at' => now()]);
    }
}
