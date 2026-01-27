<?php

namespace App\Models\Club;

use App\Models\User;
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
        'attachment_url',
        'priority',
        'status',
        'metadata',
        'is_pinned',
        'scheduled_at',
        'sent_at',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_pinned' => 'boolean',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

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

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isScheduled()
    {
        return $this->status === 'scheduled';
    }

    public function isSent()
    {
        return $this->status === 'sent';
    }

    public function markAsSent()
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

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
