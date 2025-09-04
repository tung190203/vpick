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
        'yard_type'
    ];

    const TYPE_INDOOR = 1;
    const TYPE_OUTDOOR = 2;
    const TYPE_PRIVATE_RENTAL = 3;
    const TYPE_PAY_FEE = 4;
    const TYPE_ROOF = 5;
    const YARD_TYPE = [
        self::TYPE_INDOOR,
        self::TYPE_OUTDOOR,
        self::TYPE_PRIVATE_RENTAL,
        self::TYPE_PAY_FEE,
        self::TYPE_ROOF
    ];

    public function getYardTypeNameAttribute()
    {
        return match ($this->yard_type) {
            self::TYPE_INDOOR => 'Trong nhà',
            self::TYPE_OUTDOOR => 'Ngoài trời',
            self::TYPE_PRIVATE_RENTAL => 'Thuê riêng',
            self::TYPE_PAY_FEE => 'Đóng phí',
            self::TYPE_ROOF => 'Mái che',
            default => 'Unknown',
        };
    }

    public function competitionLocation()
    {
        return $this->belongsTo(CompetitionLocation::class);
    }
}
