<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VnduprHistory extends Model
{
    use HasFactory;

    protected $table = 'vndupr_history';
    protected $fillable = [
        'user_id',
        'match_id',
        'score_before',
        'score_after',
        'match_rating',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function match()
    {
        return $this->belongsTo(Matches::class, 'match_id');
    }
    public function getMatchRatingAttribute($value)
    {
        return number_format($value, 2);
    }
    public function setMatchRatingAttribute($value)
    {
        $this->attributes['match_rating'] = number_format($value, 2);
    }
    public function getScoreBeforeAttribute($value)
    {
        return (int) $value;
    }
    public function setScoreBeforeAttribute($value)
    {
        $this->attributes['score_before'] = (int) $value;
    }
    public function getScoreAfterAttribute($value)
    {
        return (int) $value;
    }
    public function setScoreAfterAttribute($value)
    {
        $this->attributes['score_after'] = (int) $value;
    }
    public function getUpdatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
    }
    public function setUpdatedAtAttribute($value)
    {
        $this->attributes['updated_at'] = \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
