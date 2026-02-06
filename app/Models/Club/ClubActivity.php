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

    /**
     * Wallet transactions for this activity's fee (In, Confirmed) — used for collected_amount.
     */
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
        return $this->status === ClubActivityStatus::Scheduled;
    }

    public function markAsCompleted()
    {
        $this->update(['status' => ClubActivityStatus::Completed]);
    }

    /**
     * Số tiền mỗi người phải nộp khi tham gia (theo fee_split_type).
     */
    public function getFeeAmountPerParticipant(): float
    {
        $splitType = $this->fee_split_type ?? ClubActivityFeeSplitType::Fixed;

        // Quỹ bao: không thu phí từ người tham gia
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

    /**
     * Check if activity is recurring
     */
    public function isRecurring(): bool
    {
        return $this->recurring_schedule !== null && !empty($this->recurring_schedule);
    }

    /**
     * Get recurring_schedule attribute with transformed recurring_date
     */
    public function getRecurringScheduleAttribute($value)
    {
        if (!$value) {
            return null;
        }

        $data = json_decode($value, true);
        if (!$data || !isset($data['period'])) {
            return null;
        }

        // Build standardized response structure
        $result = [
            'period' => $data['period'],
            'week_days' => null,
            'recurring_date' => null,
        ];

        // Set week_days for weekly period
        if ($data['period'] === 'weekly') {
            $result['week_days'] = $data['week_days'] ?? null;
        } else {
            // Transform recurring_date to Vietnamese description for non-weekly periods
            if (isset($data['recurring_date']) && $data['recurring_date']) {
                $result['recurring_date'] = $this->formatRecurringDateForOutput(
                    $data['period'],
                    $data['recurring_date']
                );
            }
        }

        return $result;
    }

    /**
     * Set recurring_schedule attribute
     */
    public function setRecurringScheduleAttribute($value)
    {
        if (!$value) {
            $this->attributes['recurring_schedule'] = null;
            return;
        }

        // Store as JSON
        $this->attributes['recurring_schedule'] = json_encode($value);
    }

    /**
     * Format recurring_date for output (Vietnamese description)
     */
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

    /**
     * Parse date string from various formats
     */
    private function parseDate(string $dateString): ?array
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
}
