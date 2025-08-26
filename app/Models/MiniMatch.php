<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiniMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'mini_tournament_id',
        'round',
        'participant1_id',
        'participant2_id',
        'scheduled_at',
        'referee_id',
        'status',
        'participant_win_id',
    ];

    const PER_PAGE = 10;

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DISPUTED = 'disputed';

    public function participant1()
    {
        return $this->belongsTo(MiniParticipant::class, 'participant1_id');
    }
    public function participant2()
    {
        return $this->belongsTo(MiniParticipant::class, 'participant2_id');
    }

    public function participantWin()
    {
        return $this->belongsTo(MiniParticipant::class, 'participant_win_id');
    }
    public function results()
    {
        return $this->hasMany(MiniMatchResult::class, 'mini_match_id');
    }

    public function miniTournament()
    {
        return $this->belongsTo(MiniTournament::class, 'mini_tournament_id');
    }

    public function scopeWithFullRelations($query)
    {
        return $query->with([
            'participant1.user',
            'participant1.team.members.user',
            'participant2.user',
            'participant2.team.members.user',
            'results.participant.user',
            'results.participant.team.members.user',
            'participantWin.user',
            'participantWin.team.members.user',
        ]);
    }
}
