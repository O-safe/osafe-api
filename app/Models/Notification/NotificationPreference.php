<?php

namespace App\Models\Notification;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $table = 'notification_preferences';
    protected $primaryKey = 'preference_id';

    protected $fillable = [
        'user_id',
        'notification_type',
        'channel',
        'enabled',
        'quiet_hours_enabled',
        'quiet_from',
        'quiet_until',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'quiet_hours_enabled' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
