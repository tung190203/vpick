<?php

namespace App\Enums;

use Carbon\Carbon;

enum RecurringType: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match($this) {
            self::Weekly => 'Hàng tuần',
            self::Monthly => 'Hàng tháng',
            self::Quarterly => 'Hàng quý',
            self::Yearly => 'Hàng năm',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Parse recurring_date string from various formats
     * 
     * @param string $dateString Format: dd/MM/yyyy, dd-MM-yyyy, or yyyy-MM-dd
     * @return array|null ['day' => int, 'month' => int, 'year' => int]
     */
    public static function parseRecurringDate(string $dateString): ?array
    {
        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d'];
        
        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($date && $date->format($format) === $dateString) {
                    return [
                        'day' => $date->day,
                        'month' => $date->month,
                        'year' => $date->year,
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Format recurring_date to Vietnamese description
     * 
     * @param string $period 'monthly', 'quarterly', or 'yearly'
     * @param int $day Day of month
     * @param int $month Month (for yearly only)
     * @return string|null
     */
    public static function formatRecurringDate(string $period, int $day, ?int $month = null): ?string
    {
        return match($period) {
            'monthly' => "ngày {$day} hàng tháng",
            'quarterly' => "ngày {$day} tháng đầu tiên hàng quý",
            'yearly' => $month ? "ngày {$day}/{$month} hàng năm" : null,
            default => null
        };
    }
}
