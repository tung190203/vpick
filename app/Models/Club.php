<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    use HasFactory;

    const PER_PAGE = 10;

    protected $fillable = [
        'name',
        'location',
        'logo_url',
        'created_by',
    ];

    public function members()
    {
        return $this->belongsToMany(User::class, 'club_members')
            ->withPivot('is_manager')
            ->withTimestamps();
    }

    public function scopeSearch($query, $fillable, $searchTerm)
    {
        if ($searchTerm) {
            $query->where(function ($q) use ($fillable, $searchTerm) {
                foreach ($fillable as $field) {
                    $q->orWhere($field, 'LIKE', '%' . $searchTerm . '%');
                }
            });
        }
        return $query;
    }
}
