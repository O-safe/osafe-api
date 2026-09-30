<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    protected $table = 'webhook_events';
    protected $primaryKey = 'webhook_event_id';

    protected $fillable = [
        'event_id',
        'source',
        'event_type',
        'payload',
        'status',
        'attempts',
        'failure_reason',
        'ip_address',
        'signature',
        'signature_verified',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'signature_verified' => 'boolean',
        'processed_at' => 'datetime',
    ];
}
