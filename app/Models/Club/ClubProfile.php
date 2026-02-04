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
        'qr_code_image_url',
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

    /**
     * Trả về URL đầy đủ của cover image (lưu trong DB là path).
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        $value = $this->attributes['cover_image_url'] ?? null;
        if (empty($value)) {
            return null;
        }
        return str_starts_with($value, 'http') ? $value : asset('storage/' . $value);
    }

    /**
     * Lấy raw path từ DB (không qua accessor) - dùng cho việc xóa ảnh
     */
    public function getRawCoverImagePath(): ?string
    {
        return $this->attributes['cover_image_url'] ?? null;
    }

    /**
     * Trả về URL đầy đủ của QR code image.
     */
    public function getQrCodeImageUrlAttribute(): ?string
    {
        $value = $this->attributes['qr_code_image_url'] ?? null;
        if (empty($value)) {
            return null;
        }
        return str_starts_with($value, 'http') ? $value : asset('storage/' . $value);
    }

    /**
     * Lấy raw path QR code từ DB - dùng cho việc xóa ảnh
     */
    public function getRawQrCodeImagePath(): ?string
    {
        return $this->attributes['qr_code_image_url'] ?? null;
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }
}
