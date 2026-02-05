<?php

namespace App\Enums;

enum ClubActivityFeeSplitType: string
{
    case Equal = 'equal';
    case Fixed = 'fixed';
    case Fund = 'fund';

    public function label(): string
    {
        return match($this) {
            self::Equal => 'Chia đều',
            self::Fixed => 'Cố định',
            self::Fund => 'Quỹ bao',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::Equal => 'Tổng chi phí chia đều cho số người tham gia',
            self::Fixed => 'Mỗi người đóng một khoản cố định',
            self::Fund => 'Quỹ tự động thanh toán, không thu phí từ người tham gia',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
