<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    protected $table = 'reports';
    protected $primaryKey = 'report_id';

    protected $fillable = [
        'requester_type',
        'requester_id',
        'title',
        'type',
        'parameters',
        'file_path',
        'status',
    ];

    protected $casts = [
        'parameters' => 'array',
    ];

    public function requester(): MorphTo
    {
        return $this->morphTo('requester', 'requester_type', 'requester_id');
    }
}
