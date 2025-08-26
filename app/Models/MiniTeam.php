<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiniTeam extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'mini_tournament_id',
    ];

    public function tournament()
    {
        return $this->belongsTo(MiniTournament::class, 'mini_tournament_id');
    }
    public function members()
    {
        return $this->hasMany(MiniTeamMember::class, 'mini_team_id');
    }
}
