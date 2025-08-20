<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'image_url',
        'link',
        'type',
        'is_active',
        'order',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];
}
