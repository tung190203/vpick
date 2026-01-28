<?php

namespace App\Enums;

enum ClubMonthlyFeePaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Chờ thanh toán',
            self::Paid => 'Đã thanh toán',
            self::Failed => 'Thanh toán thất bại',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
