<?php

namespace App\Enums;

enum ClubMembershipStatus: string
{
    case Pending = 'pending';
    case Joined = 'joined';
    case Rejected = 'rejected';
    case Left = 'left';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ duyệt',
            self::Joined => 'Đã tham gia',
            self::Rejected => 'Đã từ chối',
            self::Left => 'Đã rời',
            self::Cancelled => 'Đã hủy',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
