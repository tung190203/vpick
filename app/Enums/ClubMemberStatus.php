<?php

namespace App\Enums;

enum ClubMemberStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ duyệt',
            self::Active => 'Đang tham gia',
            self::Inactive => 'Đã rời',
            self::Suspended => 'Bị đuổi',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
