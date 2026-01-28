<?php

namespace App\Enums;

enum ClubNotificationPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match($this) {
            self::Low => 'Thấp',
            self::Normal => 'Bình thường',
            self::High => 'Cao',
            self::Urgent => 'Khẩn cấp',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
