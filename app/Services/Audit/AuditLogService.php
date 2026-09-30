<?php

namespace App\Services\Audit;

use App\Models\Admin\Staff;
use App\Models\System\AuditLog;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    /**
     * Record a security / domain audit log event in audit_logs table.
     */
    public function log(
        ?Model $actor,
        string $action,
        ?string $resourceType = null,
        ?string $resourceId = null,
        ?array $before = null,
        ?array $after = null,
        ?array $metadata = null
    ): AuditLog {
        $actorId = 'system';
        $actorType = 'system';

        if ($actor instanceof User) {
            $actorId = $actor->user_id;
            $actorType = 'user';
        } elseif ($actor instanceof Staff) {
            $actorId = $actor->staff_id;
            $actorType = 'staff';
        }

        return AuditLog::create([
            'actor_id' => $actorId,
            'actor_type' => $actorType,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => (string) $resourceId,
            'before' => $before,
            'after' => $after,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
