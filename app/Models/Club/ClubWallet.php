<?php

namespace App\Models\Club;

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
        return $this->hasMany(ClubWalletTransaction::class)->where('status', 'confirmed');
    }

    public function getBalanceAttribute()
    {
        $in = $this->confirmedTransactions()->where('direction', 'in')->sum('amount');
        $out = $this->confirmedTransactions()->where('direction', 'out')->sum('amount');
        return $in - $out;
    }

    public function isMain()
    {
        return $this->type === 'main';
    }

    public function isFund()
    {
        return $this->type === 'fund';
    }

    public function isDonation()
    {
        return $this->type === 'donation';
    }
}
