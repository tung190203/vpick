<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        'thumbnail',
        'location_id',
        'about',
        'google_id',
        'facebook_id',
        'role',
        'email_verified_at',
        'is_profile_completed',
        'gender',
        'date_of_birth',
        'latitude',
        'longitude',
        'address',
        'last_login',
        'visibility',
        'phone',
        'self_score',
        'apple_id',
        'total_matches'
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

    const NO_PUBLIC = 3;

    const GENDER = [
        self::MALE,
        self::FEMALE,
        self::OTHER,
        self::NO_PUBLIC
    ];

    const MORNING = 'morning';
    const AFTERNOON = 'afternoon';
    const EVENING = 'evening';
    const PLAY_TIME_OPTIONS = [
        self::MORNING,
        self::AFTERNOON,
        self::EVENING
    ];

    const VISIBILITY_PUBLIC = 'open';
    const VISIBILITY_FRIEND_ONLY = 'friend-only';
    const VISIBILITY_PRIVATE = 'private';
    const VISIBILITY_OPTIONS = [
        self::VISIBILITY_PUBLIC,
        self::VISIBILITY_FRIEND_ONLY,
        self::VISIBILITY_PRIVATE
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
            self::NO_PUBLIC => 'Không tiết lộ',
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

    public function followings()
    {
        return $this->hasMany(Follow::class, 'user_id');
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
        return $query->with(['referee', 'follows', 'playTimes', 'sports', 'sports.sport', 'sports.scores', 'clubs.members']);
    }

    public function scopeLoadFullRelations()
    {
        return $this->load(['referee', 'follows', 'playTimes', 'sports', 'sports.sport', 'sports.scores', 'clubs']);
    }

    public function isMutualWith(User $other): bool
    {
        return $this->followings()
            ->where('followable_type', User::class)
            ->where('followable_id', $other->id)
            ->exists()
            &&
            $other->followings()
                ->where('followable_type', User::class)
                ->where('followable_id', $this->id)
                ->exists();
    }

    public function scopeVisibleFor($query, User $currentUser)
    {
        return $query->where(function ($q) use ($currentUser) {
            // Luôn thấy user open
            $q->where('visibility', 'open');

            $q->orWhere(function ($q2) use ($currentUser) {
                $q2->where('visibility', 'friend-only')
                    ->whereExists(function ($sub) use ($currentUser) {
                        $sub->select(DB::raw(1))
                            ->from('follows as f1')
                            ->join('follows as f2', function ($join) {
                                $join->on('f1.user_id', '=', 'f2.followable_id')
                                    ->on('f1.followable_id', '=', 'f2.user_id')
                                    ->where('f1.followable_type', User::class)
                                    ->where('f2.followable_type', User::class);
                            })
                            ->where('f1.user_id', $currentUser->id)
                            ->whereColumn('f1.followable_id', 'users.id');
                    });
            });
        });
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
                isset($filters['gender']),
                fn ($query) => $query->where('gender', $filters['gender'])
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
                                    ->from('user_sport')
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
                fn($query) => $query->where(function ($q) {
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

    public function scopeOrderByDistance($query, $lat, $lng)
    {
        return $query
            ->select('*')
            ->selectRaw("
                (
                    6371 * acos(
                        cos(radians(?))
                        * cos(radians(latitude))
                        * cos(radians(longitude) - radians(?))
                        + sin(radians(?))
                        * sin(radians(latitude))
                    )
                ) AS distance
            ", [$lat, $lng, $lat])
            ->orderByRaw('latitude IS NULL OR longitude IS NULL') // 👈 NULL xuống cuối
            ->orderBy('distance', 'asc');
    }
    // User.php
    public function isFriendWith(User $otherUser): bool
    {
        return $this->followings()
            ->where('followable_id', $otherUser->id)
            ->where('followable_type', User::class)
            ->exists()
            && $otherUser->followings()
                ->where('followable_id', $this->id)
                ->where('followable_type', User::class)
                ->exists();
    }

    public function isFollowing(?User $otherUser): bool
    {
        if (!$otherUser) {
            return false;
        }
        return $this->followings()
            ->where('followable_id', $otherUser->id)
            ->where('followable_type', User::class)
            ->exists();
    }

    public function friends()
    {
        $userClass = config('auth.providers.users.model', User::class);
        $userId = $this->id;

        return User::query()
            ->whereExists(function ($q) use ($userId, $userClass) {
                $q->select(DB::raw(1))
                    ->from('follows as f1')
                    ->whereColumn('f1.followable_id', 'users.id')
                    ->where('f1.user_id', $userId)
                    ->where('followable_type', $userClass); // không alias ở đây
            })
            ->whereExists(function ($q) use ($userId, $userClass) {
                $q->select(DB::raw(1))
                    ->from('follows as f2')
                    ->whereColumn('f2.user_id', 'users.id')
                    ->where('f2.followable_id', $userId)
                    ->where('followable_type', $userClass); // không alias ở đây
            });
    }

    public function vnduprScoresBySport($sportId = null)
    {
        $query = $this->hasManyThrough(
            UserSportScore::class,
            UserSport::class,
            'user_id',
            'user_sport_id',
            'id',
            'id'
        )->where('score_type', 'vndupr_score');

        if ($sportId) {
            $query->where('user_sport.sport_id', $sportId);
        }

        return $query;
    }

    // User.php
    public function scores()
    {
        return $this->hasManyThrough(
            UserSportScore::class,
            UserSport::class,
            'user_id',       // khóa ngoại ở bảng trung gian (user_sport)
            'user_sport_id', // khóa ngoại ở bảng cuối cùng (user_sport_score)
            'id',            // local key của User
            'id'             // local key của UserSport
        );
    }
}
