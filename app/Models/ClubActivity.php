<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'title',
        'description',
        'type',
        'start_time',
        'end_time',
        'location',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    // ========== RELATIONSHIPS ==========

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        return $this->hasMany(ClubActivityParticipant::class);
    }

    public function acceptedParticipants()
    {
        return $this->hasMany(ClubActivityParticipant::class)->where('status', 'accepted');
    }

    public function attendedParticipants()
    {
        return $this->hasMany(ClubActivityParticipant::class)->where('status', 'attended');
    }

    // ========== SCOPES ==========

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('start_time', [$from, $to]);
    }

    // ========== HELPER METHODS ==========

    public function isScheduled()
    {
        return $this->status === 'scheduled';
    }

    public function isOngoing()
    {
        return $this->status === 'ongoing';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function canBeCancelled()
    {
        return $this->status === 'scheduled';
    }

    public function markAsCompleted()
    {
        $this->update(['status' => 'completed']);
    }
}
