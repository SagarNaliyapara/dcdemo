<?php

namespace App\Models;

use Database\Factories\NotificationRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationRule extends Model
{
    /** @use HasFactory<NotificationRuleFactory> */
    use HasFactory;

    protected $casts = [
        'filters_json' => 'array',
        'date_scope_value' => 'integer',
        'email_row_limit' => 'integer',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'last_queued_at' => 'datetime',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'last_result_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
