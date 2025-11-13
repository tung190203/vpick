<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoolAdvancementRule extends Model
{
    protected $fillable = [
        'tournament_type_id',
        'group_id',
        'rank',
        'next_match_id',
        'next_position',
    ];

    public function tournamentType()
    {
        return $this->belongsTo(TournamentType::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function nextMatch()
    {
        return $this->belongsTo(Matches::class, 'next_match_id');
    }
}