<?php

namespace App\Enums;

enum ClubReportReasonType: string
{
    case Spam = 'spam';
    case Inappropriate = 'inappropriate';
    case Fraud = 'fraud';
    case Harassment = 'harassment';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spam => 'Spam',
            self::Inappropriate => 'Nội dung không phù hợp',
            self::Fraud => 'Lừa đảo',
            self::Harassment => 'Quấy rối',
            self::Other => 'Khác',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
