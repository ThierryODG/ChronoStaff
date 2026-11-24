<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderDispatch extends Model
{
    protected $fillable = [
        'company_id',
        'reminder_date',
        'reminder_time',
        'message_hash',
        'message',
        'status',
        'recipient_count',
        'sent_count',
        'failed_count',
        'last_error',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'recipient_count' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
