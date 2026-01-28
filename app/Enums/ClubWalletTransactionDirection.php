<?php

namespace App\Enums;

enum ClubWalletTransactionDirection: string
{
    case In = 'in';
    case Out = 'out';

    public function label(): string
    {
        return match($this) {
            self::In => 'Thu',
            self::Out => 'Chi',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
