<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'location',
        'club_id',
        'created_by',
        'type',
        'level',
        'description',
    ];

    const STATUS_UPCOMING = 'upcoming';
    const STATUS_ONGOING = 'ongoing';
    const STATUS_FINISHED = 'finished';

    public function club()
    {
        return $this->belongsTo(Club::class, 'club_id');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function matches()
    {
        return $this->hasMany(Matches::class);
    }
    public function participants()
    {
        return $this->hasMany(Participant::class);
    }
}
