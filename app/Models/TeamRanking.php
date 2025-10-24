<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeamRanking extends Model
{
    use HasFactory;
    protected $fillable = [
        'tournament_type_id',
        'team_id',
        'rank'
    ];

    public function tournamentType()
    {
        return $this->belongsTo(TournamentType::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
