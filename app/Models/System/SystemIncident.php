<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class SystemIncident extends Model
{
    protected $table = 'system_incidents';
    protected $primaryKey = 'incident_id';

    protected $fillable = [
        'title',
        'description',
        'status',
        'severity',
        'started_at',
        'resolved_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];
}
