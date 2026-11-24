<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Kiosk extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'device_code',
        'access_key',
        'is_active',
        'last_seen_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Generate unique device code like KIOSK-7A9B-4C2D
     */
    public static function generateDeviceCode(): string
    {
        do {
            $code = 'KIOSK-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        } while (static::where('device_code', $code)->exists());

        return $code;
    }
}

