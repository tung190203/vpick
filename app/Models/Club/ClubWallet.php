<?php

namespace App\Models\Club;

use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'currency',
        'qr_code_url',
        'qr_note',
    ];

    protected $casts = [];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function transactions()
    {
        return $this->hasMany(ClubWalletTransaction::class);
    }

    public function confirmedTransactions()
    {
        return $this->hasMany(ClubWalletTransaction::class)->where('status', ClubWalletTransactionStatus::Confirmed);
    }

    /**
     * Giao dịch tính vào quỹ chung (included_in_club_fund = true hoặc null).
     * Dùng để tính balance quỹ chung - loại trừ giao dịch có included_in_club_fund = false.
     */
    public function clubFundTransactions()
    {
        return $this->transactions()->where(function ($q) {
            $q->where('included_in_club_fund', true)->orWhereNull('included_in_club_fund');
        });
    }

    public function getBalanceAttribute()
    {
        $base = $this->confirmedTransactions()->where(function ($q) {
            $q->where('included_in_club_fund', true)->orWhereNull('included_in_club_fund');
        });
        $in = (clone $base)->where('direction', ClubWalletTransactionDirection::In)->sum('amount');
        $out = (clone $base)->where('direction', ClubWalletTransactionDirection::Out)->sum('amount');
        return $in - $out;
    }
}
