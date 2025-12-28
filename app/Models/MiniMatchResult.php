<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MiniMatchResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'mini_match_id',
        'team_id',
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

    public function team()
    {
        return $this->belongsTo(MiniTeam::class,'team_id');
    }
}
