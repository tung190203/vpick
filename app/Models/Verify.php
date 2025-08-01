<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verify extends Model
{
    use HasFactory;

    protected $table = 'verifies';
    protected $fillable = [
        'user_id',
        'vndupr_score',
        'certified_file',
        'status',
        'verifier_id',
        'approver_id',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    protected $casts = [
        'vndupr_score' => 'decimal:1',
        'user_id' => 'integer',
        'verifier_id' => 'integer',
        'approver_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }
}
