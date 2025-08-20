<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug'
    ];
    public static function boot()
    {
        parent::boot();

        static::creating(function ($location) {
            if (empty($location->slug)) {
                $location->slug = Str::slug($location->name);
            }
        });
        static::updating(function ($location) {
            if (empty($location->slug)) {
                $location->slug = Str::slug($location->name);
            }
        });
    }

    public function scopeSearch($query, $field, $searchTerm)
    {
        $value = trim($searchTerm);
    
        if (empty($field) || empty($value)) {
            return $query;
        }
    
        return $query->where($field, 'like', '%' . $value . '%');
    }
}
