<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class ValidRecurringSchedule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Null is valid
        if ($value === null) {
            return;
        }

        // Must be an array (JSON decoded)
        if (!is_array($value)) {
            $fail('Lịch lặp lại phải là một object JSON hợp lệ.');
            return;
        }

        // Must have 'period' field
        if (!isset($value['period'])) {
            $fail('Lịch lặp lại phải có field "period".');
            return;
        }

        $period = $value['period'];
        $validPeriods = ['weekly', 'monthly', 'quarterly', 'yearly'];

        // Validate period value
        if (!in_array($period, $validPeriods)) {
            $fail('Field "period" phải là một trong: weekly, monthly, quarterly, yearly.');
            return;
        }

        // Validate based on period
        switch ($period) {
            case 'weekly':
                $this->validateWeekly($value, $fail);
                break;

            case 'monthly':
            case 'quarterly':
            case 'yearly':
                $this->validateWithDate($value, $fail, $period);
                break;
        }
    }

    /**
     * Validate weekly recurring schedule
     */
    private function validateWeekly(array $value, Closure $fail): void
    {
        // week_days must be an array
        if (!isset($value['week_days']) || !is_array($value['week_days'])) {
            $fail('Với period "weekly", field "week_days" phải là một array.');
            return;
        }

        // week_days must not be empty
        if (empty($value['week_days'])) {
            $fail('Field "week_days" không được để trống.');
            return;
        }

        // Each day must be 0-6
        foreach ($value['week_days'] as $day) {
            if (!is_numeric($day) || $day < 0 || $day > 6) {
                $fail('Mỗi ngày trong "week_days" phải là số từ 0 đến 6 (0=CN, 1=T2, ..., 6=T7).');
                return;
            }
        }

        // recurring_date must be null
        if (isset($value['recurring_date']) && $value['recurring_date'] !== null) {
            $fail('Với period "weekly", field "recurring_date" phải là null.');
            return;
        }
    }

    /**
     * Validate monthly/quarterly/yearly recurring schedule
     */
    private function validateWithDate(array $value, Closure $fail, string $period): void
    {
        // week_days must be null
        if (isset($value['week_days']) && $value['week_days'] !== null) {
            $fail("Với period \"{$period}\", field \"week_days\" phải là null.");
            return;
        }

        // recurring_date must exist and be a string
        if (!isset($value['recurring_date']) || !is_string($value['recurring_date'])) {
            $fail("Với period \"{$period}\", field \"recurring_date\" phải là một string.");
            return;
        }

        // Validate date format (dd/MM/yyyy, dd-MM-yyyy, yyyy-MM-dd, hoặc có thêm HH:mm:ss)
        $dateString = $value['recurring_date'];
        if (!$this->isValidDateString($dateString)) {
            $fail('Field "recurring_date" phải là ngày hợp lệ với format: dd/MM/yyyy, dd-MM-yyyy, yyyy-MM-dd, hoặc yyyy-MM-dd HH:mm:ss.');
            return;
        }
    }

    /**
     * Check if date string is valid (chấp nhận cả date và datetime)
     */
    private function isValidDateString(string $dateString): bool
    {
        $formats = [
            'd/m/Y',
            'd-m-Y',
            'Y-m-d',
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd/m/Y H:i:s',
            'd-m-Y H:i:s',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($date && $date->format($format) === $dateString) {
                    return true;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return false;
    }
}
