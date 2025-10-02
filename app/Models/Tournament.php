<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'poster',
        'name',
        'sport_id',
        'start_date',
        'registration_open_at',
        'registration_closed_at',
        'early_registration_deadline',
        'duration',
        'min_level',
        'max_level',
        'age_group',
        'gender_policy',
        'participant',
        'max_team',
        'player_per_team',
        'max_player',
        'fee',
        'standard_fee_amount',
        'is_private',
        'auto_approve',
        'end_date',
        'location',
        'club_id',
        'created_by',
        'description',
    ];

    const PER_PAGE = 10;

    const ALL_AGES = 1;
    const YOUTH = 2; // dưới 18
    const ADULT = 3; // từ 18 - 55
    const  SENIOR = 4; // trên 55

    const AGES = [
        self::ALL_AGES,
        self::YOUTH,
        self::ADULT,
        self::SENIOR,
    ];

    const MALE = 1;
    const FEMALE = 2;
    const MIXED = 3;

    const GENDER = [
        self::MALE,
        self::FEMALE,
        self::MIXED,
    ];

    const DRAFT = 1;
    const OPEN = 2;
    const CLOSED = 3;
    const CANCELLED = 4;

    const STATUS = [
        self::DRAFT,
        self::OPEN,
        self::CLOSED,
        self::CANCELLED,
    ];

    public function club()
    {
        return $this->belongsTo(Club::class, 'club_id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tournamentTypes()
    {
        return $this->hasMany(TournamentType::class, 'tournament_id');
    }

    public function groups()
    {
        return $this->hasManyThrough(Group::class, TournamentType::class);
    }

    public function participants()
    {
        return $this->hasMany(Participant::class, 'tournament_id');
    }

    public function matches()
    {
        return $this->hasManyThrough(Matches::class, Group::class);
    }

    Public function sport()
    {
        return $this->belongsTo(Sport::class, 'sports_id');
    }

    public function tournamentStaffs()
    {
        return $this->hasMany(TournamentStaff::class, 'tournament_id');
    }

    public function scopeWithFullRelations($query)
    {
        return $query->with([
            'createdBy',
            'club',
            'sport',
            'tournamentTypes.groups.matches.participant1.user',
            'tournamentTypes.groups.matches.participant1.team.members',
            'tournamentTypes.groups.matches.participant2.user',
            'tournamentTypes.groups.matches.participant2.team.members',
            'tournamentStaffs',
            'tournamentStaffs.user',
            'participants',
        ]);
    }

    public function scopeWithBasicRelations($query)
    {
        return $query->with(['createdBy', 'club', 'sport', 'tournamentStaffs'] );
    }
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now());
    }
    public function scopeOngoing($query)
    {
        return $query->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }
    public function scopeFinished($query)
    {
        return $query->where('end_date', '<', now());
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where('name', 'like', '%' . $keyword . '%');
    }

    public function scopeFilterByDate($query, $startDate = null, $endDate = null)
    {
        return $query
            ->when($startDate, fn($q) => $q->whereDate('start_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('end_date', '<=', $endDate));
    }

    public function getAgeGroupTextAttribute()
    {
        return match ($this->age_group) {
            self::ALL_AGES => 'Mọi lứa tuổi',
            self::YOUTH => 'Thiếu niên (dưới 18)',
            self::ADULT => 'Người lớn (18-55)',
            self::SENIOR => 'Cao tuổi (trên 55)',
            default => 'Không xác định',
        };
    }

    public function getGenderPolicyTextAttribute()
    {
        return match ($this->gender_policy) {
            self::MIXED => 'Nam Nữ',
            self::MALE => 'Nam',
            self::FEMALE => 'Nữ',
            default => 'Không xác định',
        };
    }

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            self::DRAFT => 'Bản nháp',
            self::OPEN => 'Mở đăng ký',
            self::CLOSED => 'Đóng đăng ký',
            self::CANCELLED => 'Hủy',
            default => 'Không xác định',
        };
    }

    public function getPosterUrlAttribute()
    {
        return $this->poster ? asset('storage/' . $this->poster) : null;
    }
}
