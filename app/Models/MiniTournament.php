<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        switch($this->gender_policy) {
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
        switch($this->role_type) {
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
        switch($this->status) {
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

}
