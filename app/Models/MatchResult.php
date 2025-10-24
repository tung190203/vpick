<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchResult extends Model
{
    use HasFactory;

    protected $table = 'match_results';
    protected $fillable = [
        'match_id',
        'team_id',
        'score',
        'set_number',
        'won_match',
        'confirmed',
    ];
    public function match()
    {
        return $this->belongsTo(Matches::class, 'match_id');
    }
    public function participant()
    {
        return $this->belongsTo(Participant::class, 'participant_id');
    }
}