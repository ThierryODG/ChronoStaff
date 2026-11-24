<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department_id',
        'type', // 'in' or 'out'
        'punched_at',
        'offline_id', // client UUID for idempotence
        'latitude',
        'longitude',
        'distance_meters',
        'is_on_site',
        'is_manual',
        'admin_note',
        'synced_at',
    ];

    protected $casts = [
        'punched_at' => 'datetime',
        'synced_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'distance_meters' => 'float',
        'is_on_site' => 'boolean',
        'is_manual' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}

