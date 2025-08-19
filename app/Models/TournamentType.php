<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TournamentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'type',
        'description',
    ];

    const TYPE_SINGLE = 'single';
    const TYPE_DOUBLE = 'double';
    const TYPE_MIXED = 'mixed';
    const TYPES = [
        self::TYPE_SINGLE,
        self::TYPE_DOUBLE,
        self::TYPE_MIXED,
    ];
    public function tournament()
    {
        return $this->belongsTo(Tournament::class, 'tournament_id');
    }
    public function groups()
    {
        return $this->hasMany(Group::class, 'tournament_type_id');
    }
    public function getTypeAttribute($value)
    {
        return in_array($value, self::TYPES) ? $value : null;
    }
    public function setTypeAttribute($value)
    {
        if (in_array($value, self::TYPES)) {
            $this->attributes['type'] = $value;
        } else {
            throw new \InvalidArgumentException("Invalid tournament type: $value");
        }
    }
}
