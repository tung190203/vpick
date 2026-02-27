<?php

namespace App\Models\Club;

use App\Enums\ClubActivityFeeSplitType;
use App\Enums\ClubActivityParticipantStatus;
use App\Enums\ClubActivityStatus;
use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionSourceType;
use App\Enums\ClubWalletTransactionStatus;
use App\Models\User;
use App\Models\MiniTournament;
use App\Models\Club\ClubWalletTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ClubActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'mini_tournament_id',
        'title',
        'description',
        'type',
        'recurring_schedule',
        'start_time',
        'end_time',
        'duration',
        'address',
        'latitude',
        'longitude',
        'cancellation_deadline',
        'reminder_minutes',
        'status',
        'created_by',
        'cancellation_reason',
        'cancelled_by',
        'fee_amount',
        'fee_description',
        'guest_fee',
        'penalty_amount',
        'fee_split_type',
        'allow_member_invite',
        'is_public',
        'max_participants',
        'qr_code_url',
        'check_in_token',
        'creator_always_join',
    ];

    protected $casts = [
        'status' => ClubActivityStatus::class,
        'fee_split_type' => ClubActivityFeeSplitType::class,
        // NOTE: Do NOT cast recurring_schedule to 'array' - accessor will handle it
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'cancellation_deadline' => 'datetime',
        'fee_amount' => 'decimal:2',
        'guest_fee' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'latitude' => 'float',
        'longitude' => 'float',
        'allow_member_invite' => 'boolean',
        'is_public' => 'boolean',
        'creator_always_join' => 'boolean',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function miniTournament()
    {
        return $this->belongsTo(MiniTournament::class);
    }

    public function participants()
    {
        return $this->hasMany(ClubActivityParticipant::class);
    }

    public function activityFeeTransactions()
    {
        return $this->hasMany(ClubWalletTransaction::class, 'source_id')
            ->where('source_type', ClubWalletTransactionSourceType::Activity)
            ->where('direction', ClubWalletTransactionDirection::In)
            ->where('status', ClubWalletTransactionStatus::Confirmed);
    }

    public function acceptedParticipants()
    {
        return $this->hasMany(ClubActivityParticipant::class)->where('status', ClubActivityParticipantStatus::Accepted);
    }

    public function attendedParticipants()
    {
        return $this->hasMany(ClubActivityParticipant::class)->where('status', ClubActivityParticipantStatus::Attended);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', ClubActivityStatus::Scheduled);
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', ClubActivityStatus::Ongoing);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', ClubActivityStatus::Completed);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('start_time', [$from, $to]);
    }

    public function isScheduled()
    {
        return $this->status === ClubActivityStatus::Scheduled;
    }

    public function isOngoing()
    {
        return $this->status === ClubActivityStatus::Ongoing;
    }

    public function isCompleted()
    {
        return $this->status === ClubActivityStatus::Completed;
    }

    public function canBeCancelled()
    {
        return in_array($this->status, [ClubActivityStatus::Scheduled, ClubActivityStatus::Ongoing]);
    }

    public function markAsCompleted()
    {
        $this->update(['status' => ClubActivityStatus::Completed]);
    }

    public function getFeeAmountPerParticipant(): float
    {
        $splitType = $this->fee_split_type ?? ClubActivityFeeSplitType::Fixed;

        if ($splitType === ClubActivityFeeSplitType::Fund) {
            return 0;
        }

        if ((float) $this->fee_amount <= 0) {
            return 0;
        }

        if ($splitType === ClubActivityFeeSplitType::Equal) {
            $n = max(1, (int) ($this->max_participants ?? 1));
            return round((float) $this->fee_amount / $n, 2);
        }

        return (float) $this->fee_amount;
    }

    public function isRecurring(): bool
    {
        return $this->recurring_schedule !== null && !empty($this->recurring_schedule);
    }

    /** Số phút trước start_time (để hiển thị "30 phút" hoặc "1 Tiếng"). */
    public function getCancellationDeadlineMinutesAttribute(): ?int
    {
        if (!$this->cancellation_deadline || !$this->start_time) {
            return null;
        }
        $minutes = $this->cancellation_deadline->diffInMinutes($this->start_time, false);
        return $minutes > 0 ? (int) $minutes : null;
    }

    /** Số giờ trước start_time (cho backward compat, làm tròn). */
    public function getCancellationDeadlineHoursAttribute(): ?float
    {
        $minutes = $this->cancellation_deadline_minutes;
        return $minutes !== null ? round($minutes / 60, 1) : null;
    }

    public function getRecurringScheduleAttribute($value)
    {
        if (!$value) {
            return null;
        }

        $data = json_decode($value, true);
        if (!$data || !isset($data['period'])) {
            return null;
        }

        $result = [
            'period' => $data['period'],
            'week_days' => null,
            'recurring_date' => null,
        ];

        if ($data['period'] === 'weekly') {
            $result['week_days'] = $data['week_days'] ?? null;
        } else {
            if (isset($data['recurring_date']) && $data['recurring_date']) {
                $result['recurring_date'] = $this->formatRecurringDateForOutput(
                    $data['period'],
                    $data['recurring_date']
                );
            }
        }

        return $result;
    }

    public function setRecurringScheduleAttribute($value)
    {
        if (!$value) {
            $this->attributes['recurring_schedule'] = null;
            return;
        }

        // Store as JSON
        $this->attributes['recurring_schedule'] = json_encode($value);
    }

    private function formatRecurringDateForOutput(string $period, string $dateString): ?string
    {
        $date = $this->parseDate($dateString);
        if (!$date) {
            return null;
        }

        return match($period) {
            'monthly' => "ngày {$date['day']} hàng tháng",
            'quarterly' => "ngày {$date['day']} tháng đầu tiên hàng quý",
            'yearly' => "ngày {$date['day']}/{$date['month']} hàng năm",
            default => null
        };
    }

    private function parseDate(string $dateString): ?array
    {
        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'Y-m-d H:i:s', 'Y-m-d H:i', 'd/m/Y H:i:s', 'd-m-Y H:i:s'];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($date) {
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

    public function getRecurringScheduleRaw(): ?array
    {
        $value = $this->attributes['recurring_schedule'] ?? null;
        if (!$value) {
            return null;
        }

        $data = json_decode($value, true);
        return $data && isset($data['period']) ? $data : null;
    }

    public function calculateNextOccurrence(Carbon $fromDate = null): ?Carbon
    {
        if (!$this->isRecurring()) {
            return null;
        }

        $schedule = $this->getRecurringScheduleRaw();
        if (!$schedule) {
            return null;
        }

        $fromDate = $fromDate ?? Carbon::now();
        $period = $schedule['period'];

        return match($period) {
            'weekly' => $this->calculateNextWeeklyOccurrence($fromDate, $schedule['week_days'] ?? []),
            'monthly' => $this->calculateNextMonthlyOccurrence($fromDate, $schedule['recurring_date'] ?? null),
            'quarterly' => $this->calculateNextQuarterlyOccurrence($fromDate, $schedule['recurring_date'] ?? null),
            'yearly' => $this->calculateNextYearlyOccurrence($fromDate, $schedule['recurring_date'] ?? null),
            default => null
        };
    }

    private function calculateNextWeeklyOccurrence(Carbon $fromDate, array $weekDays): ?Carbon
    {
        if (empty($weekDays)) {
            return null;
        }

        sort($weekDays);

        $currentDayOfWeek = $fromDate->dayOfWeek;

        $timeString = $this->start_time?->format('H:i:s') ?? $fromDate->format('H:i:s');

        foreach ($weekDays as $targetDay) {
            if ($targetDay > $currentDayOfWeek) {
                $daysToAdd = $targetDay - $currentDayOfWeek;
                return $fromDate->copy()->addDays($daysToAdd)->setTimeFromTimeString($timeString);
            }
        }

        $daysToAdd = 7 - $currentDayOfWeek + $weekDays[0];
        return $fromDate->copy()->addDays($daysToAdd)->setTimeFromTimeString($timeString);
    }

    private function calculateNextMonthlyOccurrence(Carbon $fromDate, ?string $dateString): ?Carbon
    {
        if (!$dateString) {
            return null;
        }

        $dateInfo = $this->parseDate($dateString);
        if (!$dateInfo) {
            return null;
        }

        $targetDay = $dateInfo['day'];

        $nextDate = $fromDate->copy()->day(min($targetDay, $fromDate->daysInMonth));
        if ($this->start_time) {
            $nextDate->setTimeFromTimeString($this->start_time->format('H:i:s'));
        }

        if ($nextDate->lte($fromDate)) {
            $nextDate->addMonth();
            $nextDate->day(min($targetDay, $nextDate->daysInMonth));
        }

        return $nextDate;
    }

    private function calculateNextQuarterlyOccurrence(Carbon $fromDate, ?string $dateString): ?Carbon
    {
        if (!$dateString) {
            return null;
        }

        $dateInfo = $this->parseDate($dateString);
        if (!$dateInfo) {
            return null;
        }

        $targetDay = $dateInfo['day'];

        $quarterStartMonths = [1, 4, 7, 10];
        $currentMonth = $fromDate->month;

        $timeString = $this->start_time?->format('H:i:s');

        foreach ($quarterStartMonths as $month) {
            if ($month >= $currentMonth) {
                $nextDate = $fromDate->copy()->month($month)->day(min($targetDay, Carbon::create($fromDate->year, $month)->daysInMonth));
                if ($timeString) {
                    $nextDate->setTimeFromTimeString($timeString);
                }

                if ($nextDate->gt($fromDate)) {
                    return $nextDate;
                }
            }
        }

        $nextDate = $fromDate->copy()->addYear()->month(1)->day(min($targetDay, 31));
        if ($timeString) {
            $nextDate->setTimeFromTimeString($timeString);
        }
        return $nextDate;
    }

    private function calculateNextYearlyOccurrence(Carbon $fromDate, ?string $dateString): ?Carbon
    {
        if (!$dateString) {
            return null;
        }

        $dateInfo = $this->parseDate($dateString);
        if (!$dateInfo) {
            return null;
        }

        $targetDay = $dateInfo['day'];
        $targetMonth = $dateInfo['month'];

        $nextDate = $fromDate->copy()
            ->month($targetMonth)
            ->day(min($targetDay, Carbon::create($fromDate->year, $targetMonth)->daysInMonth));

        if ($this->start_time) {
            $nextDate->setTimeFromTimeString($this->start_time->format('H:i:s'));
        }

        if ($nextDate->lte($fromDate)) {
            $nextDate->addYear();
        }

        return $nextDate;
    }

    public function cloneForNextOccurrence(): ?ClubActivity
    {
        if (!$this->isRecurring() || !$this->isCompleted()) {
            return null;
        }

        $nextStartTime = $this->calculateNextOccurrence($this->end_time ?? $this->start_time);
        if (!$nextStartTime) {
            return null;
        }

        $duration = $this->duration ?? ($this->end_time ? $this->start_time->diffInMinutes($this->end_time) : null);
        $nextEndTime = $duration ? $nextStartTime->copy()->addMinutes($duration) : null;

        $nextCancellationDeadline = null;
        if ($this->cancellation_deadline && $this->start_time) {
            $minutesBeforeStart = $this->cancellation_deadline->diffInMinutes($this->start_time, false);
            if ($minutesBeforeStart > 0) {
                $nextCancellationDeadline = $nextStartTime->copy()->subMinutes($minutesBeforeStart);
            }
        }

        $newActivity = $this->replicate([
            'status',
            'cancellation_reason',
            'cancelled_by',
            'check_in_token',
        ]);

        $newActivity->start_time = $nextStartTime;
        $newActivity->end_time = $nextEndTime;
        $newActivity->cancellation_deadline = $nextCancellationDeadline;
        $newActivity->status = ClubActivityStatus::Scheduled;
        $newActivity->save();

        ClubActivityParticipant::create([
            'club_activity_id' => $newActivity->id,
            'user_id' => $this->created_by,
            'status' => ClubActivityParticipantStatus::Accepted,
        ]);

        $checkInToken = \Illuminate\Support\Str::random(48);
        $newActivity->update(['check_in_token' => $checkInToken]);

        return $newActivity;
    }
}
