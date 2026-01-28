<?php

namespace App\Models\Club;

use App\Enums\ClubMemberRole;
use App\Enums\ClubMemberStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClubMember extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'club_members';
    protected $perPage = 10;

    protected $fillable = [
        'club_id',
        'user_id',
        'role',
        'position',
        'status',
        'message',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
        'joined_at',
        'left_at',
        'notes',
        'is_manager',
    ];

    protected $casts = [
        'role' => ClubMemberRole::class,
        'status' => ClubMemberStatus::class,
        'reviewed_at' => 'datetime',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
        'is_manager' => 'boolean',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', ClubMemberStatus::Active);
    }

    public function scopePending($query)
    {
        return $query->where('status', ClubMemberStatus::Pending);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function isAdmin()
    {
        return $this->role === ClubMemberRole::Admin;
    }

    public function isManager()
    {
        return in_array($this->role, [ClubMemberRole::Admin, ClubMemberRole::Manager]);
    }

    public function canManageFinance()
    {
        return in_array($this->role, [ClubMemberRole::Admin, ClubMemberRole::Manager, ClubMemberRole::Treasurer]);
    }

    public function canManageMembers()
    {
        return in_array($this->role, [ClubMemberRole::Admin, ClubMemberRole::Manager]);
    }

    public function isJoinRequest()
    {
        return $this->status === ClubMemberStatus::Pending;
    }
}
