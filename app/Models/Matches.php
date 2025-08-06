<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matches extends Model
{
    use HasFactory;
    protected $table = 'matches';

    protected $fillable = [
        'tournament_id',
        'round',
        'player1_id',
        'player2_id',
        'team1_id',
        'team2_id',
        'score',
        'result',
        'confirmed_by',
        'qr_confirmed',
        'referee_id',
        'status',
        'group_id',
        'tournament_type_id',
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class, 'tournament_id');
    }
    public function player1()
    {
        return $this->belongsTo(User::class, 'player1_id');
    }
    public function player2()
    {
        return $this->belongsTo(User::class, 'player2_id');
    }
    public function team1()
    {
        return $this->belongsTo(Team::class, 'team1_id');
    }
    public function team2()
    {
        return $this->belongsTo(Team::class, 'team2_id');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
}
