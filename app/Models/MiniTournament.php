<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notifiable;

class MiniTournament extends Model
{
    use HasFactory, Notifiable;
    protected $fillable = [
        'poster',
        'sport_id',
        'name',
        'description',
        'match_type',
        'starts_at',
        'duration_minutes',
        'competition_location_id',
        'is_private',
        'fee',
        'fee_amount',
        'prize_pool',
        'age_group',
        'max_players',
        'enable_dupr',
        'enable_vndupr',
        'min_rating',
        'max_rating',
        'set_number',
        'games_per_set',
        'points_difference',
        'max_points',
        'court_switch_points',
        'gender_policy',
        'repeat_type',
        'role_type',
        'lock_cancellation',
        'auto_approve',
        'allow_participant_add_friends',
        'send_notification',
        'status',
    ];

    const PER_PAGE = 15;

    const MATCH_TYPE_FRIENDLY = 1;
    const MATCH_TYPE_ROUND_ROBIN = 2;
    const MATCH_TYPE_SINGLE = 3;
    const MATCH_TYPE_DOUBLE = 4;
    const MATCH_TYPE_TRAINING = 5;
    const MATCH_TYPE_LESSON = 6;
    const MATCH_TYPE_MEETING = 7;

    const MATCH_TYPE_NUMBER = [
        self::MATCH_TYPE_FRIENDLY,
        self::MATCH_TYPE_ROUND_ROBIN,
        self::MATCH_TYPE_SINGLE,
        self::MATCH_TYPE_DOUBLE,
        self::MATCH_TYPE_TRAINING,
        self::MATCH_TYPE_LESSON,
        self::MATCH_TYPE_MEETING,
    ];

    const MALE = 1;
    const FEMALE = 2;

    const UNLIMIT = 3;

    const GENDER = [
        self::MALE,
        self::FEMALE,
        self::UNLIMIT,
    ];

    const REPEAT_ONE_WEEK = 1;
    const REPEAT_TWO_WEEKS = 2;
    const REPEAT_THREE_WEEKS = 3;
    const REPEAT_FOUR_WEEKS = 4;

    const REPEAT = [
        self::REPEAT_ONE_WEEK,
        self::REPEAT_TWO_WEEKS,
        self::REPEAT_THREE_WEEKS,
        self::REPEAT_FOUR_WEEKS,
    ];

    const ROLE_ORGANIZER = 1;
    const ROLE_ORGANIZER_AND_PARTICIPANT = 2;
    const ROLE = [
        self::ROLE_ORGANIZER,
        self::ROLE_ORGANIZER_AND_PARTICIPANT,
    ];
    const STATUS_DRAFT = 1;
    const STATUS_OPEN = 2;
    const STATUS_CLOSED = 3;
    const STATUS_CANCELLED = 4;
    const STATUS = [
        self::STATUS_DRAFT,
        self::STATUS_OPEN,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    const FEE_NONE = 'none';
    const FEE_FREE = 'free';
    const FEE_AUTO_SPLIT = 'auto_split';
    const FEE_PER_PERSON = 'per_person';
    const FEE = [
        self::FEE_NONE,
        self::FEE_FREE,
        self::FEE_AUTO_SPLIT,
        self::FEE_PER_PERSON,
    ];

    const LOCK_1_HOUR = 1;
    const LOCK_2_HOURS = 2;
    const LOCK_4_HOURS = 3;
    const LOCK_6_HOURS = 4;
    const LOCK_8_HOURS = 5;
    const LOCK_12_HOURS = 6;
    const LOCK_24_HOURS = 7;

    const LOCK_CANCELLATION = [
        self::LOCK_1_HOUR,
        self::LOCK_2_HOURS,
        self::LOCK_4_HOURS,
        self::LOCK_6_HOURS,
        self::LOCK_8_HOURS,
        self::LOCK_12_HOURS,
        self::LOCK_24_HOURS,
    ];

    const ALL_AGES = 1;
    const YOUTH = 2;
    const ADULT = 3;
    const SENIOR = 4;
    const AGE_GROUP = [
        self::ALL_AGES,
        self::YOUTH,
        self::ADULT,
        self::SENIOR,
    ];

    public function getMatchTypeTextAttribute()
    {
        switch ($this->match_type) {
            case 1:
                return 'Giao hữu';
            case 2:
                return 'Vòng tròn';
            case 3:
                return 'Đánh đơn';
            case 4:
                return 'Đánh đôi';
            case 5:
                return 'Tập luyện';
            case 6:
                return 'Buổi học';
            case 7:
                return 'Họp mặt';
            default:
                return 'Unknown Match Type';
        }
    }

    public function getAgeGroupTextAttribute()
    {
        switch ($this->age_group) {
            case self::ALL_AGES:
                return 'Không giới hạn';
            case self::YOUTH:
                return 'Thiếu niên (Dưới 18)';
            case self::ADULT:
                return 'Người lớn (18 - 55)';
            case self::SENIOR:
                return 'Cao tuổi (Trên 55)';
            default:
                return 'Chưa xác định';
        }
    }

    public function getGenderPolicyTextAttribute()
    {
        switch ($this->gender_policy) {
            case 1:
                return 'Nam';
            case 2:
                return 'Nữ';
            case 3:
                return 'Không giới hạn';
            default:
                return 'Chưa xác định';
        }
    }

    public function getRepeatTypeTextAttribute()
    {
        switch ($this->repeat_type) {
            case 1:
                return 'Lặp lại trong 1 tuần';
            case 2:
                return 'Lặp lại trong 2 tuần';
            case 3:
                return 'Lặp lại trong 3 tuần';
            case 4:
                return 'Lặp lại trong 4 tuần';
            default:
                return 'Chưa xác định';
        }
    }

    public function getRoleTypeTextAttribute()
    {
        switch ($this->role_type) {
            case 1:
                return 'Ban tổ chức';
            case 2:
                return 'Người tham gia';
            default:
                return 'Chưa xác định';
        }
    }

    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case 1:
                return 'Nháp';
            case 2:
                return 'Mở';
            case 3:
                return 'Đóng';
            case 4:
                return 'Hủy';
            default:
                return 'Chưa xác định';
        }
    }

    public function participants()
    {
        return $this->hasMany(MiniParticipant::class);
    }

    // Sport
    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function competitionLocation()
    {
        return $this->belongsTo(CompetitionLocation::class);
    }

    public function miniTournamentStaffs()
    {
        return $this->hasMany(MiniTournamentStaff::class, 'mini_tournament_id');
    }

    public function staff()
    {
        return $this->belongsToMany(User::class, 'mini_tournament_staff')
            ->withPivot('role')
            ->withTimestamps();
    }


    public function scopeWithFullRelations($query)
    {
        return $query->with([
            'sport',
            'competitionLocation',
            'participants.user',
            'participants.team',
            'participants.team.members.user',
            'participants.user.sports.scores',
            'miniTournamentStaffs',
            'miniTournamentStaffs.user',
            'staff',
        ]);
    }

    public function loadFullRelations()
    {
        return $this->load([
            'sport',
            'competitionLocation',
            'participants.user',
            'participants.team.members.user',
            'miniTournamentStaffs',
            'miniTournamentStaffs.user',
            'staff',
        ]);
    }

    public function getAllUsersAttribute(): Collection
    {
        $directUsers = $this->participants
            ->where('type', 'user')
            ->pluck('user')
            ->filter();

        $teamUsers = collect();
        foreach ($this->participants->where('type', 'team') as $teamParticipant) {
            if ($teamParticipant->team && $teamParticipant->team->members) {
                $teamUsers = $teamUsers->merge(
                    $teamParticipant->team->members->pluck('user')->filter()
                );
            }
        }

        return $directUsers->merge($teamUsers)->unique('id')->values();
    }

    public function hasOrganizer(int $userId): bool
    {
        return $this->staff->contains(
            fn($staff) =>
            (int) $staff->pivot->user_id === $userId
                && (int) $staff->pivot->role === MiniTournamentStaff::ROLE_ORGANIZER
        );
    }

    public function scopeFilter($query, $filter)
    {
        return $query
            ->when(
                !empty($filter['sport_id']),
                fn($q) => $q->whereHas(
                    'sport',
                    fn($sq) => $sq->where('id', $filter['sport_id'])
                )
            )
            ->when(
                !empty($filter['location_id']),
                fn($q) => $q->whereHas(
                    'competitionLocation',
                    fn($lq) => $lq->where('location_id', $filter['location_id'])
                )
            )
            ->when(
                !empty($filter['keyword']),
                fn($q) => $q->where(function ($kq) use ($filter) {
                    $kq->where('name', 'like', '%' . $filter['keyword'] . '%')
                        ->orWhereHas('competitionLocation', function ($locSub) use ($filter) {
                            $locSub->where('name', 'like', '%' . $filter['keyword'] . '%')
                                ->orWhere('address', 'like', '%' . $filter['keyword'] . '%')
                                ->orWhereHas(
                                    'location',
                                    fn($lq) => $lq->where('name', 'like', '%' . $filter['keyword'] . '%')
                                );
                        });
                })
            )
            ->when(
                !empty($filter['date_from']),
                fn($q) => $q->whereBetween('starts_at', [
                    Carbon::parse($filter['date_from'])->startOfDay(),
                    Carbon::parse($filter['date_from'])->endOfDay()
                ])
            )
            ->when(
                !empty($filter['type']) && is_array($filter['type']),
                function ($q) use ($filter) {
                    $q->where(function ($subQuery) use ($filter) {
                        foreach ($filter['type'] as $type) {
                            if ($type === 'single') {
                                $subQuery->orWhere('match_type', self::MATCH_TYPE_SINGLE);
                            } elseif ($type === 'double') {
                                $subQuery->orWhere('match_type', self::MATCH_TYPE_DOUBLE);
                            }
                        }
                    });
                }
            )
            ->when(!empty($filter['rating']), function ($q) use ($filter) {
                $q->where(function ($outer) use ($filter) {
                    foreach ($filter['rating'] as $rating) {
                        $outer->orWhere(function ($sub) use ($rating) {
                            $sub
                                ->where(function ($c) use ($rating) {
                                    $c->whereNull('min_rating')
                                        ->orWhereRaw('CAST(min_rating AS DECIMAL(10,2)) <= ?', [$rating]);
                                })
                                ->where(function ($c) use ($rating) {
                                    $c->whereNull('max_rating')
                                        ->orWhereRaw('CAST(max_rating AS DECIMAL(10,2)) >= ?', [$rating]);
                                });
                        });
                    }
                });
            })
            ->when(
                !empty($filter['fee']) && is_array($filter['fee']),
                function ($q) use ($filter) {
                    $q->where(function ($subQuery) use ($filter) {
                        foreach ($filter['fee'] as $fee) {
                            if ($fee === 'free') {
                                $subQuery->orWhere(function ($cond) {
                                    $cond->where('fee', self::FEE_FREE)
                                        ->orWhere(function ($inner) {
                                            $inner->where('fee', self::FEE_NONE)
                                                ->where('fee_amount', 0);
                                        });
                                });
                            } elseif ($fee === 'paid') {
                                $min = $filter['min_price'] ?? 0;
                                $max = $filter['max_price'] ?? PHP_INT_MAX;

                                $subQuery->orWhere(function ($paid) use ($min, $max) {
                                    $paid->where(function ($perPerson) use ($min, $max) {
                                        $perPerson->where('fee', self::FEE_PER_PERSON)
                                            ->whereBetween('fee_amount', [$min, $max]);
                                    })
                                        ->orWhere(function ($autoSplit) use ($min, $max) {
                                            $autoSplit->where('fee', self::FEE_AUTO_SPLIT)
                                                ->whereRaw('(prize_pool / NULLIF(max_players, 0)) BETWEEN ? AND ?', [$min, $max]);
                                        });
                                });
                            }
                        }
                    });
                }
            )
            ->when(
                !empty($filter['time_of_day']) && is_array($filter['time_of_day']),
                function ($q) use ($filter) {
                    $q->where(function ($subQuery) use ($filter) {
                        foreach ($filter['time_of_day'] as $timeOfDay) {
                            if ($timeOfDay === 'morning') {
                                $subQuery->orWhereTime('starts_at', '<', '11:00:00');
                            } elseif ($timeOfDay === 'afternoon') {
                                $subQuery->orWhere(function ($timeQuery) {
                                    $timeQuery->whereTime('starts_at', '>=', '11:00:00')
                                        ->whereTime('starts_at', '<', '16:00:00');
                                });
                            } elseif ($timeOfDay === 'evening') {
                                $subQuery->orWhereTime('starts_at', '>=', '16:00:00');
                            }
                        }
                    });
                }
            )
            ->when(
                !empty($filter['slot_status']) && is_array($filter['slot_status']),
                function ($q) use ($filter) {
                    $q->where(function ($subQuery) use ($filter) {
                        foreach ($filter['slot_status'] as $slotStatus) {
                            if ($slotStatus === 'one_slot') {
                                $subQuery->orWhereRaw('(
                                COALESCE(max_players, 0) - (
                                    SELECT COUNT(*) 
                                    FROM mini_participants 
                                    WHERE mini_participants.mini_tournament_id = mini_tournaments.id
                                )
                            ) >= 1');
                            } elseif ($slotStatus === 'two_slot') {
                                $subQuery->orWhereRaw('(
                                COALESCE(max_players, 0) - (
                                    SELECT COUNT(*) 
                                    FROM mini_participants 
                                    WHERE mini_participants.mini_tournament_id = mini_tournaments.id
                                )
                            ) >= 2');
                            } elseif ($slotStatus === 'full_slot') {
                                $subQuery->orWhereRaw('(
                                SELECT COUNT(*) 
                                FROM mini_participants 
                                WHERE mini_participants.mini_tournament_id = mini_tournaments.id
                            ) = 0');
                            }
                        }
                    });
                }
            )
            ->when(true, function ($q) {
                $userId = auth()->id();

                $q->where(function ($sub) use ($userId) {
                    $sub->where('is_private', '!=', 1)
                        ->whereNotIn('status', [1, 3, 4]);

                    if ($userId) {
                        $sub->orWhere(function ($visible) use ($userId) {
                            $visible
                                ->orWhereHas('miniTournamentStaffs', function ($staffQuery) use ($userId) {
                                    $staffQuery->where('user_id', $userId)
                                        ->where('role', MiniTournamentStaff::ROLE_ORGANIZER);
                                })
                                ->orWhereHas('participants', function ($partQuery) use ($userId) {
                                    $partQuery->where('user_id', $userId);
                                });
                        });
                    }
                });
            });
    }

    public function scopeNearBy($query, $lat, $lng, $radius)
    {
        $haversine = "(6371 * acos(cos(radians($lat)) 
                        * cos(radians(competition_locations.latitude)) 
                        * cos(radians(competition_locations.longitude) 
                        - radians($lng)) 
                        + sin(radians($lat)) 
                        * sin(radians(competition_locations.latitude))))";

        return $query->whereHas('competitionLocation', function ($q) use ($haversine, $radius) {
            $q->havingRaw("$haversine < ?", [$radius]);
        });
    }

    public function scopeInBounds($query, $minLat, $maxLat, $minLng, $maxLng)
    {
        return $query->whereHas('competitionLocation', function ($q) use ($minLat, $maxLat, $minLng, $maxLng) {
            $q->whereBetween('latitude', [$minLat, $maxLat])
                ->whereBetween('longitude', [$minLng, $maxLng]);
        });
    }
}
