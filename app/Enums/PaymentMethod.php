<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case QrCode = 'qr_code';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::Cash => 'Tiền mặt',
            self::BankTransfer => 'Chuyển khoản',
            self::QrCode => 'QR Code',
            self::Other => 'Khác',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
