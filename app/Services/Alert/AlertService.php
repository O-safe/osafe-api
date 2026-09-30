<?php

namespace App\Services\Alert;

use App\Enums\AlertStatus;
use App\Models\Admin\Staff;
use App\Models\Notification\Alert;
use App\Models\User\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;

class AlertService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function createAlert(array $data): Alert
    {
        $alert = Alert::create(array_merge($data, [
            'type' => $data['type'] ?? 'sos',
            'status' => $data['status'] ?? AlertStatus::Unread,
            'is_read' => false,
            'is_resolved' => false,
            'triggered_at' => $data['triggered_at'] ?? now(),
        ]));

        \App\Events\AlertCreated::dispatch($alert);

        return $alert;
    }

    public function markAsRead(Model $actor, Alert $alert): Alert
    {
        $oldStatus = is_object($alert->status) ? $alert->status->value : (string) $alert->status;

        $alert->update([
            'status' => AlertStatus::Read,
            'is_read' => true,
            'read_at' => now(),
        ]);

        \App\Events\AlertStatusChanged::dispatch($alert, $oldStatus, AlertStatus::Read->value);

        return $alert;
    }

    public function resolveAlert(Model $actor, Alert $alert): Alert
    {
        $actorId = $actor instanceof User ? $actor->user_id : ($actor instanceof Staff ? $actor->staff_id : 'system');
        $oldStatus = is_object($alert->status) ? $alert->status->value : (string) $alert->status;

        $alert->update([
            'status' => AlertStatus::Resolved,
            'is_resolved' => true,
            'resolved_by' => $actorId,
            'resolved_at' => now(),
        ]);

        \App\Events\AlertStatusChanged::dispatch($alert, $oldStatus, AlertStatus::Resolved->value);

        $this->auditLogService->log(
            $actor,
            'alert.resolved',
            Alert::class,
            (string) $alert->alert_id,
            null,
            ['resolved_by' => $actorId]
        );

        return $alert;
    }
}
