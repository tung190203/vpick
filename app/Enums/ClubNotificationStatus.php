<?php

namespace App\Enums;

enum ClubNotificationStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Sent = 'sent';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft => 'Bản nháp',
            self::Scheduled => 'Đã lên lịch',
            self::Sent => 'Đã gửi',
            self::Cancelled => 'Đã hủy',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
