<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'time_rounding',
        'break_threshold_hours',
        'break_deduction_minutes',
        'cooldown_enabled',
        'min_punch_interval_minutes',
        'timezone',
        'work_start_time',
        'work_end_time',
        'overtime_enabled',
        'require_onsite_for_reports',
        'kiosk_mode_enabled',
        'individual_login_enabled',
        'reminder_time',
        'reminder_message',
        'reminder_enabled',
        'reminders',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'break_threshold_hours' => 'float',
        'break_deduction_minutes' => 'integer',
        'cooldown_enabled' => 'boolean',
        'min_punch_interval_minutes' => 'integer',
        'overtime_enabled' => 'boolean',
        'require_onsite_for_reports' => 'boolean',
        'kiosk_mode_enabled' => 'boolean',
        'individual_login_enabled' => 'boolean',
        'reminder_enabled' => 'boolean',
        'reminders' => 'array',
    ];

    public function kiosks(): HasMany
    {
        return $this->hasMany(Kiosk::class);
    }

    public function justifications(): HasMany
    {
        return $this->hasMany(Justification::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function timeLogs(): HasManyThrough
    {
        return $this->hasManyThrough(TimeLog::class, User::class);
    }

    /**
     * Calculate distance in meters between given lat/lng and company location.
     * Uses the Haversine formula.
     */
    public function distanceTo(?float $lat, ?float $lng): ?float
    {
        if ($this->latitude === null || $this->longitude === null || $lat === null || $lng === null) {
            return null;
        }

        $earthRadius = 6371000; // in meters

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
        $latTo = deg2rad($lat);
        $lonTo = deg2rad($lng);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 2);
    }
}

