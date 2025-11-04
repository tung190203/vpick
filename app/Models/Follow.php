<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Follow extends Model
{
    use HasFactory, Notifiable;
    protected $table = 'follows';
    protected $fillable = [
        'user_id',
        'followable_id',
        'followable_type',
    ];

    const PER_PAGE = 15;

    public function followable()
    {
        return $this->morphTo();
    }
}
