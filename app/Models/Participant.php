<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_type_id',
        'type',
        'user_id',
        'team_id',
        'is_confirmed',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
    public function tournamentType()
    {
        return $this->belongsTo(TournamentType::class, 'tournament_type_id');
    }
}
