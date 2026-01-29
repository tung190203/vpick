<?php

namespace App\Models\Club;

use App\Enums\ClubActivityParticipantStatus;
use App\Enums\ClubActivityStatus;
use App\Models\User;
use App\Models\MiniTournament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'mini_tournament_id',
        'title',
        'description',
        'type',
        'is_recurring',
        'recurring_schedule',
        'start_time',
        'end_time',
        'location',
        'reminder_minutes',
        'status',
        'created_by',
        'cancellation_reason',
        'cancelled_by',
        'fee_amount',
        'penalty_percentage',
    ];

    protected $casts = [
        'status' => ClubActivityStatus::class,
        'is_recurring' => 'boolean',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'fee_amount' => 'decimal:2',
        'penalty_percentage' => 'decimal:2',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function miniTournament()
    {
        return $this->belongsTo(MiniTournament::class);
    }

    public function participants()
    {
        return $this->hasMany(ClubActivityParticipant::class);
    }

    public function acceptedParticipants()
    {
        return $this->hasMany(ClubActivityParticipant::class)->where('status', ClubActivityParticipantStatus::Accepted);
    }

    public function attendedParticipants()
    {
        return $this->hasMany(ClubActivityParticipant::class)->where('status', ClubActivityParticipantStatus::Attended);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', ClubActivityStatus::Scheduled);
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', ClubActivityStatus::Ongoing);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', ClubActivityStatus::Completed);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('start_time', [$from, $to]);
    }

    public function isScheduled()
    {
        return $this->status === ClubActivityStatus::Scheduled;
    }

    public function isOngoing()
    {
        return $this->status === ClubActivityStatus::Ongoing;
    }

    public function isCompleted()
    {
        return $this->status === ClubActivityStatus::Completed;
    }

    public function canBeCancelled()
    {
        return $this->status === ClubActivityStatus::Scheduled;
    }

    public function markAsCompleted()
    {
        $this->update(['status' => ClubActivityStatus::Completed]);
    }
}
