<?php

namespace App\Enums;

enum ClubWalletTransactionSourceType: string
{
    case MonthlyFee = 'monthly_fee';
    case FundCollection = 'fund_collection';
    case Expense = 'expense';
    case Donation = 'donation';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match($this) {
            self::MonthlyFee => 'Phí hàng tháng',
            self::FundCollection => 'Đợt thu quỹ',
            self::Expense => 'Chi phí',
            self::Donation => 'Quyên góp',
            self::Adjustment => 'Điều chỉnh',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
