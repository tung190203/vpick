<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'user_id',
        'is_confirmed',
        'is_invite_by_organizer'
    ];

    const PER_PAGE = 15;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tournament()
    {
        return $this->belongsTo(Tournament::class, 'tournament_id');
    }

    public static function scopeWithFullRelations($query)
    {
        return $query->with('user', 'tournament', 'user.sports.scores');
    }
}
