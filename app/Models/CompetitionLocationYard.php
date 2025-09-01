<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompetitionLocationYard extends Model
{
    use HasFactory;
    protected $fillable = [
        'competition_location_id',
        'yard_number',
    ];

    public function competitionLocation()
    {
        return $this->belongsTo(CompetitionLocation::class);
    }
}
