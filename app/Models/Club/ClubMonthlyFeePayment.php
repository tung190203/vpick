<?php

namespace App\Models\Club;

use App\Enums\ClubMonthlyFeePaymentStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubMonthlyFeePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'club_monthly_fee_id',
        'user_id',
        'period',
        'amount',
        'wallet_transaction_id',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'status' => ClubMonthlyFeePaymentStatus::class,
        'amount' => 'decimal:2',
        'period' => 'date',
        'paid_at' => 'datetime',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function monthlyFee()
    {
        return $this->belongsTo(ClubMonthlyFee::class, 'club_monthly_fee_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function walletTransaction()
    {
        return $this->belongsTo(ClubWalletTransaction::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', ClubMonthlyFeePaymentStatus::Pending);
    }

    public function scopePaid($query)
    {
        return $query->where('status', ClubMonthlyFeePaymentStatus::Paid);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', ClubMonthlyFeePaymentStatus::Failed);
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    public function markAsPaid()
    {
        $this->update([
            'status' => ClubMonthlyFeePaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed()
    {
        $this->update([
            'status' => ClubMonthlyFeePaymentStatus::Failed,
        ]);
    }
}
