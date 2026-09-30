<?php

namespace App\Models\Notification;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationTemplate extends Model
{
    protected $table = 'notification_templates';
    protected $primaryKey = 'template_id';

    protected $fillable = [
        'slug',
        'name',
        'channel',
        'subject',
        'body',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function notifications(): HasMany
    {
        return $this->hasMany(OsafeNotification::class, 'template_id', 'template_id');
    }
}
