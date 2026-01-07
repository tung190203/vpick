<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_type_id',
        'name',
    ];

    public function tournamentType()
    {
        return $this->belongsTo(TournamentType::class, 'tournament_type_id');
    }

    public function matches()
    {
        return $this->hasMany(Matches::class, 'group_id');
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'group_team')
            ->withPivot('order')
            ->withTimestamps()
            ->orderBy('group_team.order');
    }    
}
