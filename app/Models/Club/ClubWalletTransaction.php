<?php

namespace App\Models\Club;

use App\Enums\ClubWalletTransactionDirection;
use App\Enums\ClubWalletTransactionSourceType;
use App\Enums\ClubWalletTransactionStatus;
use App\Enums\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubWalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_wallet_id',
        'direction',
        'amount',
        'source_type',
        'source_id',
        'payment_method',
        'status',
        'reference_code',
        'description',
        'created_by',
        'confirmed_by',
        'confirmed_at',
    ];

    protected $casts = [
        'direction' => ClubWalletTransactionDirection::class,
        'source_type' => ClubWalletTransactionSourceType::class,
        'payment_method' => PaymentMethod::class,
        'status' => ClubWalletTransactionStatus::class,
        'amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    public function wallet()
    {
        return $this->belongsTo(ClubWallet::class, 'club_wallet_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function source()
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', ClubWalletTransactionStatus::Pending);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', ClubWalletTransactionStatus::Confirmed);
    }

    public function scopeIncoming($query)
    {
        return $query->where('direction', ClubWalletTransactionDirection::In);
    }

    public function scopeOutgoing($query)
    {
        return $query->where('direction', ClubWalletTransactionDirection::Out);
    }

    public function isPending()
    {
        return $this->status === ClubWalletTransactionStatus::Pending;
    }

    public function isConfirmed()
    {
        return $this->status === ClubWalletTransactionStatus::Confirmed;
    }

    public function isRejected()
    {
        return $this->status === ClubWalletTransactionStatus::Rejected;
    }

    public function confirm($userId)
    {
        $this->update([
            'status' => ClubWalletTransactionStatus::Confirmed,
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
        ]);
    }

    public function reject($userId)
    {
        $this->update([
            'status' => ClubWalletTransactionStatus::Rejected,
            'confirmed_by' => $userId,
            'confirmed_at' => now(),
        ]);
    }
}
