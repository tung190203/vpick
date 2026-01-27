<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubActivityParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_activity_id',
        'user_id',
        'status',
    ];

    // ========== RELATIONSHIPS ==========

    public function activity()
    {
        return $this->belongsTo(ClubActivity::class, 'club_activity_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ========== SCOPES ==========

    public function scopeInvited($query)
    {
        return $query->where('status', 'invited');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    public function scopeAttended($query)
    {
        return $query->where('status', 'attended');
    }

    // ========== HELPER METHODS ==========

    public function accept()
    {
        $this->update(['status' => 'accepted']);
    }

    public function decline()
    {
        $this->update(['status' => 'declined']);
    }

    public function markAsAttended()
    {
        $this->update(['status' => 'attended']);
    }

    public function markAsAbsent()
    {
        $this->update(['status' => 'absent']);
    }
}
