<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class SystemHealthCheck extends Model
{
    protected $table = 'system_health_checks';
    protected $primaryKey = 'check_id';

    public $timestamps = false;

    protected $fillable = [
        'service_name',
        'status',
        'latency_ms',
        'details',
        'checked_at',
    ];

    protected $casts = [
        'latency_ms' => 'integer',
        'details' => 'array',
        'checked_at' => 'datetime',
    ];
}
