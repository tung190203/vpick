<?php

namespace App\Models\Club;

use App\Enums\ClubReportReasonType;
use App\Enums\ClubReportStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'user_id',
        'reason_type',
        'description',
        'status',
        'reviewed_by',
        'reviewed_at',
        'admin_note',
    ];

    protected $casts = [
        'reason_type' => ClubReportReasonType::class,
        'status' => ClubReportStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', ClubReportStatus::Pending);
    }
}
