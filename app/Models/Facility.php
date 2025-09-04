<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function competitionLocations()
    {
        return $this->belongsToMany(CompetitionLocation::class, 'competition_location_facility', 'facility_id', 'competition_location_id');
    }
}
