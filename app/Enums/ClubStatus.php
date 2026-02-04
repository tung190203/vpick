<?php

namespace App\Enums;

enum ClubStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Draft = 'draft';

    public function label(): string
    {
        return match($this) {
            self::Active => 'Hoạt động',
            self::Inactive => 'Không hoạt động',
            self::Suspended => 'Bị đình chỉ',
            self::Draft => 'Bản nháp',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
