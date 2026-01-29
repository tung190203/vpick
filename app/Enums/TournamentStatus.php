<?php
namespace App\Enums;

enum TournamentStatus: string
{
    case Upcoming = 'upcoming';
    case Ongoing = 'ongoing';
    case Finished = 'finished';

    public function label(): string
    {
        return match($this) {
            self::Upcoming => 'Sắp diễn ra',
            self::Ongoing => 'Đang diễn ra',
            self::Finished => 'Đã kết thúc',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function pattern(): string
    {
        return implode('|', self::values());
    }
}
