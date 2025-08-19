<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Matches extends Model
{
    use HasFactory;
    protected $table = 'matches';

    protected $fillable = [
        'group_id',
        'round',
        'participant1_id',
        'participant2_id',
        'referee_id',
        'status',
        'scheduled_at',
    ];
    public function group()
    {
        return $this->belongsTo(Group::class, 'group_id');
    }
    public function participant1()
    {
        return $this->belongsTo(Participant::class, 'participant1_id');
    }
    public function participant2()
    {
        return $this->belongsTo(Participant::class, 'participant2_id');
    }
    public function referee()
    {
        return $this->belongsTo(User::class, 'referee_id');
    }
}
