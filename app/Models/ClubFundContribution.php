<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubFundContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_fund_collection_id',
        'user_id',
        'amount',
        'wallet_transaction_id',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    // ========== RELATIONSHIPS ==========

    public function fundCollection()
    {
        return $this->belongsTo(ClubFundCollection::class, 'club_fund_collection_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function walletTransaction()
    {
        return $this->belongsTo(ClubWalletTransaction::class);
    }

    // ========== SCOPES ==========

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // ========== HELPER METHODS ==========

    public function confirm()
    {
        $this->update(['status' => 'confirmed']);
        $this->fundCollection->updateCollectedAmount();
    }

    public function reject()
    {
        $this->update(['status' => 'rejected']);
    }
}
