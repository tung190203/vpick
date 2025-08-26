<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MiniTournament extends Model
{
    use HasFactory;
    protected $fillable = [
        'poster',
        'sport_id',
        'created_by',
        'name',
        'description',
        'match_type',
        'starts_at',
        'duration_minutes',
        'competition_location_id',
        'is_private',
        'fee_amount',
        'max_players',
        'enable_dupr',
        'enable_vndupr',
        'min_rating',
        'max_rating',
        'gender_policy',
        'min_age',
        'max_age',
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

    const REPEAT_NONE = 1;
    const REPEAT_DAILY = 2;
    const REPEAT_WEEKLY = 3;
    const REPEAT_MONTHLY = 4;

    const REPEAT = [
        self::REPEAT_NONE,
        self::REPEAT_DAILY,
        self::REPEAT_WEEKLY,
        self::REPEAT_MONTHLY,
    ];

    const ROLE_ORGANIZER = 1;
    const ROLE_PARTICIPANT = 2;
    const ROLE = [
        self::ROLE_ORGANIZER,
        self::ROLE_PARTICIPANT,
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
        switch($this->repeat_type) {
            case 1:
                return 'Không lặp lại';
            case 2:
                return 'Hàng ngày';
            case 3:
                return 'Hàng tuần';
            case 4:
                return 'Hàng tháng';
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

    // Người tạo
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
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

    public function scopeWithFullRelations($query)
    {
        return $query->with([
            'sport',
            'creator',
            'competitionLocation',
            'participants.user',
            'participants.team',
            'participants.team.members.user',
        ]);
    }

    public function loadFullRelations()
{
    return $this->load([
        'sport',
        'creator',
        'participants.user',
        'participants.team.members.user',
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
}
