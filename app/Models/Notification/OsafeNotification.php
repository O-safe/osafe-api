<?php

namespace App\Models\Notification;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OsafeNotification extends Model
{
    protected $table = 'osafe_notifications';
    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'user_id',
        'alert_id',
        'template_id',
        'channel',
        'title',
        'body',
        'is_read',
        'is_sent',
        'status',
        'failure_reason',
        'sent_at',
        'read_at',
        'metadata',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class, 'alert_id', 'alert_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id', 'template_id');
    }
}
