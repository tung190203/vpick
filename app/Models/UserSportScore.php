<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSportScore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_sport_id',
        'score_type',
        'score_value'
    ];

    public function userSport()
    {
        return $this->belongsTo(UserSport::class);
    }
}
