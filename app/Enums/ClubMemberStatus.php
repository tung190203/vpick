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
        return match($this) {
            self::Pending => 'Chờ duyệt',
            self::Active => 'Hoạt động',
            self::Inactive => 'Không hoạt động',
            self::Suspended => 'Tạm ngưng',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
