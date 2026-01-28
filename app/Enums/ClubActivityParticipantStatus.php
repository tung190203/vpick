<?php

namespace App\Enums;

enum ClubActivityParticipantStatus: string
{
    case Invited = 'invited';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Attended = 'attended';
    case Absent = 'absent';

    public function label(): string
    {
        return match($this) {
            self::Invited => 'Đã mời',
            self::Accepted => 'Đã chấp nhận',
            self::Declined => 'Đã từ chối',
            self::Attended => 'Đã tham gia',
            self::Absent => 'Vắng mặt',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
