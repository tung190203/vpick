<?php

namespace App\Enums;

enum ClubReportStatus: string
{
    case Pending = 'pending';
    case Reviewed = 'reviewed';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xử lý',
            self::Reviewed => 'Đã xem xét',
            self::Resolved => 'Đã xử lý',
            self::Dismissed => 'Đã bỏ qua',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
