<?php

namespace App\Enums;

enum ClubFundContributionStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Chờ xác nhận',
            self::Confirmed => 'Đã xác nhận',
            self::Rejected => 'Đã từ chối',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
