<?php

namespace App\Services\Club;

use App\Enums\ClubMemberRole;
use App\Enums\ClubNotificationPriority;
use App\Enums\ClubNotificationStatus;
use App\Models\Club\Club;
use App\Models\Club\ClubNotification;
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

        return $notification;
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
    }

    public function markAllAsRead(Club $club, int $userId, bool $canManage): string
    {
        return DB::transaction(function () use ($club, $userId, $canManage) {
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

                return 'Đã đánh dấu đọc tất cả thông báo';
            }

            $recipients = DB::table('club_notification_recipients')
                ->join('club_notifications', 'club_notification_recipients.club_notification_id', '=', 'club_notifications.id')
                ->where('club_notifications.club_id', $club->id)
                ->where('club_notification_recipients.user_id', $userId)
                ->where('club_notification_recipients.is_read', false)
                ->update([
                    'club_notification_recipients.is_read' => true,
                    'club_notification_recipients.read_at' => now(),
                ]);

            return $recipients > 0
                ? "Đã đánh dấu đọc {$recipients} thông báo"
                : 'Không có thông báo chưa đọc';
        });
    }
}
