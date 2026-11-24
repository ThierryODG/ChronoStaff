<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'role',
        'pin_code',
        'qr_token',
        'is_active',
        'terms_accepted_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'pin_code', // Don't expose pin directly in normal API responses
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class)
            ->withPivot('hourly_rate')
            ->withTimestamps();
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['manager', 'admin']);
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    /**
     * Get the latest clock punch for this user.
     */
    public function latestTimeLog()
    {
        return $this->hasOne(TimeLog::class)->latestOfMany('punched_at');
    }

    /**
     * Generate unique 4-digit PIN and 32-char QR token.
     */
    public static function generateCredentials(): array
    {
        do {
            $pin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (static::where('pin_code', $pin)->exists());

        $qr = Str::random(32);

        return [
            'pin_code' => $pin,
            'qr_token' => $qr,
        ];
    }

    public function justifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Justification::class);
    }
}
