<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class ReadActivity extends Model
{
    /**
     * Primary key fix: migration defines id('read_activity_id') as the PK.
     * Previously this was incorrectly set to 'activity_log_id' (a FK column),
     * which caused broken Eloquent behaviour on find(), firstOrCreate(), etc.
     */
    protected $primaryKey = 'read_activity_id';
    public $incrementing = true;
    public $timestamps = false;
    protected $fillable = [
        'activity_log_id',
        'staff_id',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function activityLog()
    {
        return $this->belongsTo(ActivityLog::class, 'activity_log_id', 'activity_log_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'staff_id');
    }
}
