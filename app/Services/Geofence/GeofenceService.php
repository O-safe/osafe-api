<?php

namespace App\Services\Geofence;

use App\Models\Device\Device;
use App\Models\Geofence\Geofence;
use App\Models\User\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class GeofenceService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function createGeofence(User $user, array $data): Geofence
    {
        return DB::transaction(function () use ($user, $data) {
            $geofence = Geofence::create(array_merge($data, [
                'owner_user_id' => $user->user_id,
                'created_by' => $user->user_id,
                'is_active' => true,
            ]));

            $this->auditLogService->log(
                $user,
                'geofence.created',
                Geofence::class,
                (string) $geofence->geofence_id,
                null,
                $geofence->toArray()
            );

            return $geofence;
        });
    }

    public function attachDevice(Model $actor, Geofence $geofence, Device $device): void
    {
        $geofence->devices()->syncWithoutDetaching([$device->device_id]);

        $this->auditLogService->log(
            $actor,
            'geofence.device_attached',
            Geofence::class,
            (string) $geofence->geofence_id,
            null,
            ['device_id' => $device->device_id]
        );
    }

    public function detachDevice(Model $actor, Geofence $geofence, Device $device): void
    {
        $geofence->devices()->detach($device->device_id);

        $this->auditLogService->log(
            $actor,
            'geofence.device_detached',
            Geofence::class,
            (string) $geofence->geofence_id,
            null,
            ['device_id' => $device->device_id]
        );
    }
}
