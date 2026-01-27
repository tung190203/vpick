<?php

namespace App\Enums;

enum ClubWalletType: string
{
    case Main = 'main';
    case Fund = 'fund';
    case Donation = 'donation';

    public function label(): string
    {
        return match($this) {
            self::Main => 'Ví chính',
            self::Fund => 'Ví quỹ',
            self::Donation => 'Ví quyên góp',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
