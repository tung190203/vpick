<?php

namespace App\Models\Club;

use App\Enums\ClubFundCollectionStatus;
use App\Enums\ClubFundContributionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubFundCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'title',
        'description',
        'target_amount',
        'collected_amount',
        'currency',
        'start_date',
        'end_date',
        'status',
        'qr_code_url',
        'created_by',
    ];

    protected $casts = [
        'status' => ClubFundCollectionStatus::class,
        'target_amount' => 'decimal:2',
        'collected_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contributions()
    {
        return $this->hasMany(ClubFundContribution::class);
    }

    public function confirmedContributions()
    {
        return $this->hasMany(ClubFundContribution::class)->where('status', ClubFundContributionStatus::Confirmed);
    }

    public function scopeActive($query)
    {
        return $query->where('status', ClubFundCollectionStatus::Active);
    }

    public function scopePending($query)
    {
        return $query->where('status', ClubFundCollectionStatus::Pending);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', ClubFundCollectionStatus::Completed);
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->target_amount == 0) return 0;
        return min(100, ($this->collected_amount / $this->target_amount) * 100);
    }

    public function isActive()
    {
        return $this->status === ClubFundCollectionStatus::Active;
    }

    public function isCompleted()
    {
        return $this->status === ClubFundCollectionStatus::Completed || $this->collected_amount >= $this->target_amount;
    }

    public function updateCollectedAmount()
    {
        $this->collected_amount = $this->confirmedContributions()->sum('amount');
        $this->save();
    }
}
