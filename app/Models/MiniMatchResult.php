<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiniMatchResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'mini_match_id',
        'participant_id',
        'score',
        'won_set',
        'set_number',
        'status'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public function miniMatch()
    {
        return $this->belongsTo(MiniMatch::class, 'mini_match_id');
    }
    public function participant()
    {
        return $this->belongsTo(MiniParticipant::class, 'participant_id');
    }
}
