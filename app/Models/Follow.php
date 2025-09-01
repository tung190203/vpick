<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Follow extends Model
{
    use HasFactory;
    protected $table = 'follows';
    protected $fillable = [
        'user_id',
        'followable_id',
        'followable_type',
    ];

    public function followable()
    {
        return $this->morphTo();
    }
}
