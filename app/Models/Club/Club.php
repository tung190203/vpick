<?php

namespace App\Models\Club;

use App\Models\User;
use App\Models\Tournament;
use App\Models\MiniTournament;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Club extends Model
{
    use HasFactory, SoftDeletes;

    const PER_PAGE = 10;

    protected $fillable = [
        'name',
        'location',
        'logo_url',
        'status',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->hasMany(ClubMember::class);
    }

    public function activeMembers()
    {
        return $this->hasMany(ClubMember::class)->where('status', 'active');
    }

    public function pendingJoinRequests()
    {
        return $this->hasMany(ClubMember::class)->where('status', 'pending');
    }

    public function profile()
    {
        return $this->hasOne(ClubProfile::class);
    }

    public function wallets()
    {
        return $this->hasMany(ClubWallet::class);
    }

    public function mainWallet()
    {
        return $this->hasOne(ClubWallet::class)->where('type', 'main');
    }

    public function monthlyFees()
    {
        return $this->hasMany(ClubMonthlyFee::class);
    }

    public function activeMonthlyFees()
    {
        return $this->hasMany(ClubMonthlyFee::class)->where('is_active', true);
    }

    public function fundCollections()
    {
        return $this->hasMany(ClubFundCollection::class);
    }

    public function expenses()
    {
        return $this->hasMany(ClubExpense::class);
    }

    public function activities()
    {
        return $this->hasMany(ClubActivity::class);
    }

    public function notifications()
    {
        return $this->hasMany(ClubNotification::class);
    }

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }

    public function miniTournaments()
    {
        return $this->hasMany(MiniTournament::class);
    }

    public function scopeWithFullRelations($query)
    {
        return $query->with([
            'creator',
            'profile',
            'members.user.vnduprScores',
            'members.reviewer',
            'wallets',
            'activeMembers.user.vnduprScores'
        ]);
    }

    public function scopeSearch($query, $fillable, $searchTerm)
    {
        if ($searchTerm) {
            $query->where(function ($q) use ($fillable, $searchTerm) {
                foreach ($fillable as $field) {
                    $q->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                }
            });
        }
        return $query;
    }

    public function hasMember($userId)
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function isMember($userId)
    {
        return $this->activeMembers()->where('user_id', $userId)->exists();
    }

    public function canManage($userId)
    {
        $member = $this->members()->where('user_id', $userId)->first();
        if (!$member) return false;
        
        return in_array($member->role, ['admin', 'manager']);
    }

    public function canManageFinance($userId)
    {
        $member = $this->members()->where('user_id', $userId)->first();
        if (!$member) return false;
        
        return in_array($member->role, ['admin', 'manager', 'treasurer']);
    }
}
