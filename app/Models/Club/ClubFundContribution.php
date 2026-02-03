<?php

namespace App\Models\Club;

use App\Enums\ClubFundContributionStatus;
use App\Models\User;
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
        'receipt_url',
        'note',
        'status',
    ];

    protected $casts = [
        'status' => ClubFundContributionStatus::class,
        'amount' => 'decimal:2',
    ];

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

    public function scopePending($query)
    {
        return $query->where('status', ClubFundContributionStatus::Pending);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', ClubFundContributionStatus::Confirmed);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', ClubFundContributionStatus::Rejected);
    }

    public function confirm()
    {
        $this->update(['status' => ClubFundContributionStatus::Confirmed]);
        $this->fundCollection->updateCollectedAmount();
    }

    public function reject()
    {
        $this->update(['status' => ClubFundContributionStatus::Rejected]);
    }
}
