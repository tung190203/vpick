<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class MiniParticipant extends Model
{
    use HasFactory, Notifiable;
    protected $fillable = [
        'mini_tournament_id',
        'user_id',
        'is_confirmed',
    ];

    const PER_PAGE = 20;

    public function miniTournament()
    {
        return $this->belongsTo(MiniTournament::class);
    }

    // Nếu là user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeWithFullRelations($query) {
        return $query->with('user.sports.scores', 'user.sports.sport');
    }

    public function scopeLoadFullRelations()
    {
        return $this->load('user.sports.scores', 'user.sports.sport');
    }
}
