<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionLocation extends Model
{
    use HasFactory;

    protected $table = 'competition_locations';
    
    protected $fillable = [
        'name',
        'location_id',
        'latitude',
        'longitude',
        'image',
        'address',
        'phone',
        'opening_time',
        'closing_time',
        'note_booking',
        'website',
        'avatar_url'
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
    public function miniTournaments()
    {
        return $this->hasMany(MiniTournament::class, 'location_id');
    }
}
