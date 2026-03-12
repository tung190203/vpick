<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'play_mode',
        'format',
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
        // New fee fields
        'has_fee',
        'auto_split_court_fee',
        'payment_note',
        'qr_code_image',
        'payment_account_id',
    ];

    const PER_PAGE = 15;

    const MATCH_TYPE_FRIENDLY = 1;
    const MATCH_TYPE_SINGLE = 2;
    const MATCH_TYPE_DOUBLE = 3;
    const MATCH_TYPE_TRAINING = 4;

    const MATCH_TYPE_NUMBER = [
        self::MATCH_TYPE_FRIENDLY,
        self::MATCH_TYPE_SINGLE,
        self::MATCH_TYPE_DOUBLE,
        self::MATCH_TYPE_TRAINING,
    ];

    // Play Mode: 1=Vui vẻ, 2=Thi đấu, 3=Luyện tập
    const PLAY_MODE_FUN = 1;
    const PLAY_MODE_COMPETITIVE = 2;
    const PLAY_MODE_TRAINING = 3;
    const PLAY_MODE = [
        self::PLAY_MODE_FUN,
        self::PLAY_MODE_COMPETITIVE,
        self::PLAY_MODE_TRAINING,
    ];

    // Format: 1=Đánh đơn, 2=Đánh đôi, 3=Đôi nam, 4=Đôi nữ, 5=Mixed
    const FORMAT_SINGLE = 1;
    const FORMAT_DOUBLE = 2;
    const FORMAT_MENS_DOUBLES = 3;
    const FORMAT_WOMENS_DOUBLES = 4;
    const FORMAT_MIXED = 5;
    const FORMAT = [
        self::FORMAT_SINGLE,
        self::FORMAT_DOUBLE,
        self::FORMAT_MENS_DOUBLES,
        self::FORMAT_WOMENS_DOUBLES,
        self::FORMAT_MIXED,
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

    // Fee settings constants
    const HAS_FEE_FALSE = false;
    const HAS_FEE_TRUE = true;

    const AUTO_SPLIT_FALSE = false;
    const AUTO_SPLIT_TRUE = true;

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

    public function getPlayModeTextAttribute()
    {
        switch ($this->play_mode) {
            case self::PLAY_MODE_FUN:
                return 'Vui vẻ';
            case self::PLAY_MODE_COMPETITIVE:
                return 'Thi đấu';
            case self::PLAY_MODE_TRAINING:
                return 'Luyện tập';
            default:
                return 'Chưa xác định';
        }
    }

    public function getFormatTextAttribute()
    {
        switch ($this->format) {
            case self::FORMAT_SINGLE:
                return 'Đánh đơn';
            case self::FORMAT_DOUBLE:
                return 'Đánh đôi';
            case self::FORMAT_MENS_DOUBLES:
                return 'Đôi nam';
            case self::FORMAT_WOMENS_DOUBLES:
                return 'Đôi nữ';
            case self::FORMAT_MIXED:
                return 'Mixed';
            default:
                return 'Chưa xác định';
        }
    }

    public static function getDefaultDuprSettings(int $playMode): array
    {
        switch ($playMode) {
            case self::PLAY_MODE_COMPETITIVE:
                return ['enable_dupr' => true, 'enable_vndupr' => true];
            case self::PLAY_MODE_FUN:
            case self::PLAY_MODE_TRAINING:
            default:
                return ['enable_dupr' => false, 'enable_vndupr' => false];
        }
    }

    // Fee computed properties
    public function getHasFeeTextAttribute(): string
    {
        return $this->has_fee ? 'Có phí' : 'Miễn phí';
    }

    public function getAutoSplitTextAttribute(): string
    {
        return $this->auto_split_court_fee ? 'Chia tiền sân tự động' : 'Tiền cố định/người';
    }

    /**
     * Tính phí mỗi người dựa trên cài đặt
     * Nếu auto_split = true: fee_amount / số người tham gia thực tế
     * Nếu auto_split = false: fee_amount (tiền cố định mỗi người)
     */
    public function getFeePerPersonAttribute()
    {
        if (!$this->has_fee) {
            return 0;
        }

        if ($this->auto_split_court_fee) {
            // Chia theo số người tham gia thực tế
            $participantCount = $this->participants()->count();
            if ($participantCount > 0) {
                return round($this->fee_amount / $participantCount);
            }
            // Nếu chưa có người, trả về null hoặc có thể hiển thị "Chưa xác định"
            return null;
        }

        // Tiền cố định mỗi người
        return $this->fee_amount;
    }

    /**
     * Tính tổng tiền thu được (dự kiến)
     */
    public function getTotalFeeExpectedAttribute()
    {
        if (!$this->has_fee) {
            return 0;
        }

        $participantCount = $this->participants()->count();
        if ($participantCount > 0) {
            return $this->fee_per_person * $participantCount;
        }
        
        return 0;
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
            ->withPivot('role', 'id')
            ->withTimestamps();
    }


    public function scopeWithFullRelations($query)
    {
        return $query->with([
            'sport',
            'competitionLocation',
            'participants.user.sports.sport',
            'participants.user.sports.scores',
            'miniTournamentStaffs.user',
            'staff',
        ]);
    }

    public function loadFullRelations()
    {
        return $this->load([
            'sport',
            'competitionLocation',
            'participants.user.sports.sport',
            'participants.user.sports.scores',
            'miniTournamentStaffs.user',
            'staff',
        ]);
    }

    public function getAllUsersAttribute(): Collection
    {
        return $this->participants->pluck('user')->filter();
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
                    $kq->where('mini_tournaments.name', 'like', '%' . $filter['keyword'] . '%')
                        ->orWhereHas('competitionLocation', function ($locSub) use ($filter) {
                            $locSub->where('competition_locations.name', 'like', '%' . $filter['keyword'] . '%')
                                ->orWhere('competition_locations.address', 'like', '%' . $filter['keyword'] . '%')
                                ->orWhereHas(
                                    'location',
                                    fn($lq) => $lq->where('locations.name', 'like', '%' . $filter['keyword'] . '%')
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
                                $subQuery->orWhere('format', self::FORMAT_SINGLE);
                            } elseif ($type === 'double') {
                                $subQuery->orWhere('format', self::FORMAT_DOUBLE);
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

    public function scopeNearBy($query, $lat, $lng, $radiusKm)
    {
        $haversine = "(6371 * acos(
            cos(radians(?))
            * cos(radians(competition_locations.latitude))
            * cos(radians(competition_locations.longitude) - radians(?))
            + sin(radians(?))
            * sin(radians(competition_locations.latitude))
        ))";

        return $query->whereHas('competitionLocation', function ($q) use ($haversine, $lat, $lng, $radiusKm) {
            $q->whereRaw("$haversine < ?", [
                $lat,
                $lng,
                $lat,
                $radiusKm
            ]);
        });
    }

    public function scopeInBounds($query, $minLat, $maxLat, $minLng, $maxLng)
    {
        return $query->whereHas('competitionLocation', function ($q) use ($minLat, $maxLat, $minLng, $maxLng) {
            $q->whereBetween('latitude', [$minLat, $maxLat])
                ->whereBetween('longitude', [$minLng, $maxLng]);
        });
    }
    public function scopeOrderByDistanceFromLocation(
        Builder $query,
        float $lat,
        float $lng
    ) {
        return $query
            ->leftJoin('competition_locations', 'competition_locations.id', '=', 'mini_tournaments.competition_location_id')
            ->select('mini_tournaments.*')
            ->selectRaw("
                (
                    6371 * acos(
                        cos(radians(?))
                        * cos(radians(competition_locations.latitude))
                        * cos(radians(competition_locations.longitude) - radians(?))
                        + sin(radians(?))
                        * sin(radians(competition_locations.latitude))
                    )
                ) AS distance
            ", [$lat, $lng, $lat])
            ->orderByRaw('competition_locations.latitude IS NULL OR competition_locations.longitude IS NULL')
            ->orderBy('distance', 'asc');
    }
}
