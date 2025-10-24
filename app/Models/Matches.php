<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matches extends Model
{
    use HasFactory;
    protected $table = 'matches';

    protected $fillable = [
        'group_id',
        'tournament_type_id',
        'round',
        'next_match_id',
        'next_position',
        'loser_next_match_id',
        'loser_next_position',
        'home_team_id',
        'away_team_id',
        'leg',
        'referee_id',
        'status',
        'is_bye',
        'is_loser_bracket',
        'is_third_place',
        'scheduled_at',
        'court',
        'winner_id'
    ];
    public function tournamentType()
    {
        return $this->belongsTo(TournamentType::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function results()
    {
        return $this->hasMany(MatchResult::class, 'match_id');
    }
    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
    public function referee()
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    public function scopeWithFullRelations($query)
    {
        return $query->with([
            'group',
            'referee',
            'homeTeam',
            'homeTeam.members',
            'awayTeam',
            'awayTeam.members',
            'results',
        ]);
    }
}
