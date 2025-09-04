<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'password',
        'avatar_url',
        'location_id',
        'about',
        'google_id',
        'role',
        'email_verified_at',
        'is_profile_completed',
        'gender',
        'date_of_birth',
        'latitude',
        'longitude',
        'address',
        'last_login',
    ];

    const PER_PAGE = 15;

    const PLAYER = 'player';
    const ADMIN = 'admin';

    const REFEREE = 'referee';

    const ROLE = [
        self::PLAYER,
        self::ADMIN,
        self::REFEREE
    ];

    const MALE = 1;
    const FEMALE = 2;

    const OTHER = 0;

    const GENDER = [
        self::MALE,
        self::FEMALE,
        self::OTHER
    ];

    const MORNING = 'morning';
    const AFTERNOON = 'afternoon';
    const EVENING = 'evening';
    const PLAY_TIME_OPTIONS = [
        self::MORNING,
        self::AFTERNOON,
        self::EVENING
    ];

    const LOW_RATING = 'low';
    const MEDIUM_RATING = 'medium';
    const HIGH_RATING = 'high';

    const RECENT_MATCHES_OPTIONS = [
        self::LOW_RATING,
        self::MEDIUM_RATING,
        self::HIGH_RATING
    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'age_years',
        'age_group',
        'gender_text',
    ];

    public function getGenderText()
    {
        return match ($this->gender) {
            self::MALE => 'Nam',
            self::FEMALE => 'Nữ',
            default => 'Khác',
        };
    }

    public function getGenderTextAttribute()
    {
        return $this->getGenderText();
    }

    public function getAgeYearsAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }

        try {
            return Carbon::parse($this->date_of_birth)->age;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getAgeGroupAttribute(): ?string
    {
        $age = $this->age_years;

        if ($age === null) {
            return null;
        }

        if ($age < 10) {
            return 'Trẻ em';
        }

        if ($age >= 10 && $age <= 15) {
            return 'Thiếu niên nhỏ';
        }

        if ($age >= 16 && $age <= 17) {
            return 'Vị thành niên';
        }

        if ($age >= 18) {
            return 'Người lớn';
        }

        return null;
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = bcrypt($password);
    }
    // Lấy danh sách người dùng mà người này theo dõi
    public function follows()
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    public function referee()
    {
        return $this->hasOne(Referee::class);
    }

    public function playTimes()
    {
        return $this->hasMany(UserPlayTime::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withTimestamps();
    }

    public function sport()
    {
        return $this->belongsToMany(Sport::class, 'user_sport')
            ->withPivot('tier')
            ->withTimestamps();
    }

    public function sports()
    {
        return $this->hasMany(UserSport::class);
    }

    public function vnduprScores()
{
    return $this->hasManyThrough(
        UserSportScore::class,
        UserSport::class,
        'user_id',      // FK trên bảng user_sport
        'user_sport_id',// FK trên bảng user_sport_scores
        'id',           // PK trên users
        'id'            // PK trên user_sport
    )->where('score_type', 'vndupr_score');
}

    public function clubs()
    {
        return $this->belongsToMany(Club::class, 'club_members')
            ->withPivot(['is_manager', 'joined_at'])
            ->withTimestamps();
    }

    public function participants()
    {
        return $this->hasMany(Participant::class, 'user_id');
    }

    public function miniParticipants()
    {
        return $this->hasMany(MiniParticipant::class, 'user_id');
    }

    public function matches()
    {
        return $this->hasManyThrough(
            Matches::class,          // bảng cuối
            Participant::class,    // bảng trung gian
            'user_id',             // FK trên participants trỏ về users
            'participant1_id',     // FK trên matches trỏ về participants
            'id',                  // PK của users
            'id'                   // PK của participants
        )->orWhereHas('participant2', fn($q) => $q->where('user_id', $this->id));
        // 👆 đoạn này hơi đặc biệt, vì match có cả participant1_id và participant2_id
    }

    public function miniMatches()
    {
        return $this->hasManyThrough(
            MiniMatch::class,
            MiniParticipant::class,
            'user_id',
            'participant1_id',
            'id',
            'id'
        )->orWhereHas('participant2', fn($q) => $q->where('user_id', $this->id));
    }

    public function messagesAsSender()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function messagesAsReceiver()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function scopeWithFullRelations($query)
    {
        return $query->with(['referee', 'follows', 'playTimes', 'sports', 'sports.sport', 'sports.scores', 'clubs']);
    }

    public function scopeLoadFullRelations()
    {
        return $this->load(['referee', 'follows', 'playTimes', 'sports', 'sports.sport', 'sports.scores', 'clubs']);
    }

    public function scopeInBounds($query, $minLat, $maxLat, $minLng, $maxLng)
    {
        return $query->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng]);
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when(
                !empty($filters['keyword']),
                fn($query) => $query->where(function ($q) use ($filters) {
                    $q->where('full_name', 'like', '%' . $filters['keyword'] . '%')
                        ->orWhere('email', 'like', '%' . $filters['keyword'] . '%');
                })
            )
            ->when(
                !empty($filters['sport_id']),
                fn($query) => $query->whereHas('sports', function ($q) use ($filters) {
                    $q->where('sport_id', $filters['sport_id']);
                })
            )
            ->when(
                !empty($filters['location_id']),
                fn($query) => $query->where('location_id', $filters['location_id'])
            )
            ->when(
                !empty($filters['favourite_player']) && $filters['favourite_player'] == true,
                fn($query) => $query->whereHas('follows', function ($q) {
                    $q->where('user_id', auth()->id())
                        ->where('followable_type', User::class);
                })
            )
            ->when(
                !empty($filters['is_connected']) && $filters['is_connected'] == true,
                fn($query) => $query->where(function ($q) {
                    $q->whereHas('messagesAsSender', function ($q2) {
                        $q2->where('receiver_id', auth()->id());
                    })->orWhereHas('messagesAsReceiver', function ($q2) {
                        $q2->where('sender_id', auth()->id());
                    });
                })
            )
            ->when(
                !empty($filters['gender']) && $filters['gender'] != 0,
                fn($query) => $query->where('gender', $filters['gender'])
            )
            ->when(
                !empty($filters['time_of_day']) && is_array($filters['time_of_day']),
                fn($query) => $query->whereHas('playTimes', function ($q) use ($filters) {
                    $timeOfDayArray = $filters['time_of_day'];

                    $q->where(function ($query) use ($timeOfDayArray) {
                        foreach ($timeOfDayArray as $timeOfDay) {
                            if ($timeOfDay === 'morning') {
                                $query->orWhereTime('start_time', '<', '11:00:00');
                            } elseif ($timeOfDay === 'afternoon') {
                                $query->orWhere(function ($subQuery) {
                                    $subQuery->whereTime('start_time', '>=', '11:00:00')
                                        ->whereTime('start_time', '<=', '16:00:00');
                                });
                            } elseif ($timeOfDay === 'evening') {
                                $query->orWhereTime('start_time', '>', '16:00:00');
                            }
                        }
                    });
                })
            )
            ->when(
                !empty($filters['rating']) && is_array($filters['rating']),
                function ($query) use ($filters) {
                    $ratings = array_map('floatval', $filters['rating']);
                    $query->whereHas('sports.scores', function ($q) use ($ratings) {
                        $q->whereNotIn('score_type', ['personal_score'])
                            ->whereIn('score_value', function ($subQuery) use ($ratings) {
                                $subQuery->select('score_value')
                                    ->from('user_sports')
                                    ->whereIn('score_value', $ratings);
                            });
                    });
                }
            )
            ->when(
                !empty($filters['online_recently']) && $filters['online_recently'] == true,
                fn($query) => $query->where(
                    'last_login',
                    '>=',
                    Carbon::now()->subMinutes($filters['online_before_minutes'] ?? 30)
                )
            )
            ->when(
                !empty($filters['same_club_id']) && is_array($filters['same_club_id']),
                fn($query) => $query->whereHas('clubs', function ($q) use ($filters) {
                    $q->whereIn('clubs.id', $filters['same_club_id']);
                })
            )
            ->when(
                isset($filters['verify_profile']),
                fn($query) => $query->where('is_profile_completed', $filters['verify_profile'])
            )
            ->when(
                !empty($filters['achievement']) && $filters['achievement'] == true,
                fn($query) => $query->where(function($q) {
                    $q->whereHas('badges')
                      ->orWhereHas('sport', fn($q2) => $q2->whereNotNull('user_sport.tier')); // hoặc có tier
                })
            );            
        if (!empty($filters['recent_matches']) && is_array($filters['recent_matches'])) {
            $query->withCount([
                'matches' => fn($q) => $q->where('status', 'completed')
                    ->whereMonth('matches.created_at', now()->month)
                    ->whereYear('matches.created_at', now()->year),
                'miniMatches' => fn($q) => $q->where('status', 'completed')
                    ->whereMonth('mini_matches.created_at', now()->month)
                    ->whereYear('mini_matches.created_at', now()->year),
            ]);
        }
    }

    public function scopeNearBy($query, float $lat, float $lng, float $radiusKm = 5)
    {
        $haversine = "(6371 * acos(cos(radians($lat)) 
                * cos(radians(latitude)) 
                * cos(radians(longitude) - radians($lng)) 
                + sin(radians($lat)) 
                * sin(radians(latitude))))";

        return $query->select('*')
            ->selectRaw("$haversine AS distance")
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');
    }
}
