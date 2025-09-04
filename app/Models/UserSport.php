<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSport extends Model
{
    use HasFactory;

    protected $table = 'user_sport';

    protected $fillable = [
        'user_id',
        'sport_id',
        'tier'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }
    
    public function scores()
    {
        return $this->hasMany(UserSportScore::class);
    }    
}
