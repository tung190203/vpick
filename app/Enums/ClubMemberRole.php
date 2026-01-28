<?php

namespace App\Enums;

enum ClubMemberRole: string
{
    case Member = 'member';
    case Admin = 'admin';
    case Manager = 'manager';
    case Treasurer = 'treasurer';
    case Secretary = 'secretary';

    public function label(): string
    {
        return match($this) {
            self::Member => 'Thành viên',
            self::Admin => 'Quản trị viên',
            self::Manager => 'Quản lý',
            self::Treasurer => 'Thủ quỹ',
            self::Secretary => 'Thư ký',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
