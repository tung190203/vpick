<?php

namespace App\Models\Club;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'description',
        'cover_image_url',
        'phone',
        'email',
        'website',
        'address',
        'city',
        'province',
        'country',
        'latitude',
        'longitude',
        'social_links',
        'settings',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'social_links' => 'array',
        'settings' => 'array',
    ];

    public function club()
    {
        return $this->belongsTo(Club::class);
    }
}
