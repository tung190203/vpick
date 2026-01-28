<?php

namespace App\Models\Club;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubMonthlyFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'amount',
        'currency',
        'due_day',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function payments()
    {
        return $this->hasMany(ClubMonthlyFeePayment::class);
    }

    public function paidPayments()
    {
        return $this->hasMany(ClubMonthlyFeePayment::class)->where('status', \App\Enums\ClubMonthlyFeePaymentStatus::Paid);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
