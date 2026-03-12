<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiniRecurringSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'mini_tournament_id',
        'repeat_type',
        'repeat_days',
        'time',
        'start_date',
        'end_date',
    ];

    const REPEAT_DAILY = 'daily';
    const REPEAT_WEEKLY = 'weekly';
    const REPEAT_BIWEEKLY = 'biweekly';
    const REPEAT_MONTHLY = 'monthly';

    const REPEAT_TYPES = [
        self::REPEAT_DAILY,
        self::REPEAT_WEEKLY,
        self::REPEAT_BIWEEKLY,
        self::REPEAT_MONTHLY,
    ];

    /**
     * Get the tournament this schedule belongs to
     */
    public function miniTournament()
    {
        return $this->belongsTo(MiniTournament::class);
    }

    /**
     * Get repeat type text
     */
    public function getRepeatTypeTextAttribute(): string
    {
        return match($this->repeat_type) {
            self::REPEAT_DAILY => 'Hàng ngày',
            self::REPEAT_WEEKLY => 'Hàng tuần',
            self::REPEAT_BIWEEKLY => '2 tuần một lần',
            self::REPEAT_MONTHLY => 'Hàng tháng',
            default => 'Không xác định',
        };
    }

    /**
     * Get repeat days as array
     */
    public function getRepeatDaysArrayAttribute(): array
    {
        if (empty($this->repeat_days)) {
            return [];
        }
        
        if (is_array($this->repeat_days)) {
            return $this->repeat_days;
        }
        
        return json_decode($this->repeat_days, true) ?? [];
    }

    /**
     * Get days of week names
     */
    public function getRepeatDaysTextAttribute(): string
    {
        $days = $this->repeat_days_array;
        $dayNames = [1 => 'T2', 2 => 'T3', 3 => 'T4', 4 => 'T5', 5 => 'T6', 6 => 'T7', 7 => 'CN'];
        
        if (empty($days)) {
            return '';
        }
        
        return implode(', ', array_map(fn($d) => $dayNames[$d] ?? $d, $days));
    }
}
