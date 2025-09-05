<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiniTournamentUserNotification extends Model
{
    use HasFactory;

    protected $fillable = ['mini_tournament_id', 'user_id', 'reminded_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function miniTournament()
    {
        return $this->belongsTo(MiniTournament::class);
    }
}
