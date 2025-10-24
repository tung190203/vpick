<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'user_id',
        'type',
        'content',
        'meta',
    ];

    const PER_PAGE = 20;

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }
}
