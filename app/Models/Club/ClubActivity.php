<?php

namespace App\Models\Club;

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

class ClubActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'mini_tournament_id',
        'title',
        'description',
        'type',
        'is_recurring',
        'recurring_schedule',
        'start_time',
        'end_time',
        'address',
        'cancellation_deadline',
        'reminder_minutes',
        'status',
        'created_by',
        'cancellation_reason',
        'cancelled_by',
        'fee_amount',
        'guest_fee',
        'penalty_percentage',
        'fee_split_type',
        'allow_member_invite',
        'max_participants',
        'qr_code_url',
        'check_in_token',
    ];

    protected $casts = [
        'status' => ClubActivityStatus::class,
        'is_recurring' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'cancellation_deadline' => 'datetime',
        'fee_amount' => 'decimal:2',
        'guest_fee' => 'decimal:2',
        'penalty_percentage' => 'decimal:2',
        'allow_member_invite' => 'boolean',
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
     * fixed: fee_amount là phí/người. equal: fee_amount là tổng, chia đều cho max_participants (hoặc 1 nếu chưa set).
     */
    public function getFeeAmountPerParticipant(): float
    {
        if ((float) $this->fee_amount <= 0) {
            return 0;
        }
        if (($this->fee_split_type ?? 'fixed') === 'equal') {
            $n = max(1, (int) ($this->max_participants ?? 1));
            return round((float) $this->fee_amount / $n, 2);
        }
        return (float) $this->fee_amount;
    }
}
