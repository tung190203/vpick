<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'club_notification_type_id',
        'title',
        'content',
        'metadata',
        'is_pinned',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_pinned' => 'boolean',
    ];

    // ========== RELATIONSHIPS ==========

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function type()
    {
        return $this->belongsTo(ClubNotificationType::class, 'club_notification_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients()
    {
        return $this->hasMany(ClubNotificationRecipient::class);
    }

    public function readRecipients()
    {
        return $this->hasMany(ClubNotificationRecipient::class)->where('is_read', true);
    }

    public function unreadRecipients()
    {
        return $this->hasMany(ClubNotificationRecipient::class)->where('is_read', false);
    }

    // ========== SCOPES ==========

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // ========== HELPER METHODS ==========

    public function togglePin()
    {
        $this->update(['is_pinned' => !$this->is_pinned]);
    }

    public function getReadCountAttribute()
    {
        return $this->readRecipients()->count();
    }

    public function getUnreadCountAttribute()
    {
        return $this->unreadRecipients()->count();
    }
}
