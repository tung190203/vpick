<?php

namespace App\Models\Club;

use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionStatus;
use App\Enums\ClubWalletType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'type',
        'currency',
        'qr_code_url',
    ];

    protected $casts = [
        'type' => ClubWalletType::class,
    ];

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

    public function getBalanceAttribute()
    {
        $in = $this->confirmedTransactions()->where('direction', ClubWalletTransactionDirection::In)->sum('amount');
        $out = $this->confirmedTransactions()->where('direction', ClubWalletTransactionDirection::Out)->sum('amount');
        return $in - $out;
    }

    public function isMain()
    {
        return $this->type === ClubWalletType::Main;
    }

    public function isFund()
    {
        return $this->type === ClubWalletType::Fund;
    }

    public function isDonation()
    {
        return $this->type === ClubWalletType::Donation;
    }
}
