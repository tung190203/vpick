<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiniTournamentStaff extends Model
{
    use HasFactory;

    protected $fillable = [
        'mini_tournament_id',
        'user_id',
        'role',
    ];

    const ROLE_ORGANIZER = 1;
    const ROLE_REFEREE = 2;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public static function getRoleText($role)
    {
        return match ($role) {
            self::ROLE_ORGANIZER => 'organizer',
            self::ROLE_REFEREE => 'referee',
            default => 'unknown',
        };
    }
}
