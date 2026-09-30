<?php

namespace App\Services\Integration;

use App\Enums\DeviceStatus;
use App\Models\Device\Device;
use App\Models\Integration\DeviceIntegration;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeviceIntegrationService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function issueCredentials(Device $device, string $platform = 'osafe_tracker', ?Model $actor = null): array
    {
        return DB::transaction(function () use ($device, $platform, $actor) {
            $rawToken = 'dt_' . Str::random(40);
            $tokenHash = hash('sha256', $rawToken);

            $integration = DeviceIntegration::updateOrCreate(
                [
                    'device_id' => $device->device_id,
                    'platform' => $platform,
                ],
                [
                    'user_id' => $actor && method_exists($actor, 'getKey') ? (string) $actor->getKey() : null,
                    'is_active' => true,
                    'access_token' => $tokenHash,
                    'config' => [
                        'issued_at' => now()->toIso8601String(),
                    ],
                    'last_synced_at' => now(),
                ]
            );

            if ($actor) {
                $this->auditLogService->log(
                    $actor,
                    'device.credentials_issued',
                    DeviceIntegration::class,
                    (string) $integration->integration_id,
                    null,
                    [
                        'device_id' => $device->device_id,
                        'platform' => $platform,
                        'is_active' => true,
                    ]
                );
            }

            return [
                'device_id' => $device->device_id,
                'integration_id' => $integration->integration_id,
                'platform' => $platform,
                'device_token' => $rawToken,
            ];
        });
    }

    public function rotateCredentials(Device $device, string $platform = 'osafe_tracker', ?Model $actor = null): array
    {
        return DB::transaction(function () use ($device, $platform, $actor) {
            $integration = DeviceIntegration::where('device_id', $device->device_id)
                ->where('platform', $platform)
                ->first();

            $oldStatus = $integration ? ['is_active' => $integration->is_active] : null;

            $rawToken = 'dt_' . Str::random(40);
            $tokenHash = hash('sha256', $rawToken);

            $integration = DeviceIntegration::updateOrCreate(
                [
                    'device_id' => $device->device_id,
                    'platform' => $platform,
                ],
                [
                    'user_id' => $actor && method_exists($actor, 'getKey') ? (string) $actor->getKey() : null,
                    'is_active' => true,
                    'access_token' => $tokenHash,
                    'config' => [
                        'rotated_at' => now()->toIso8601String(),
                    ],
                    'last_synced_at' => now(),
                ]
            );

            if ($actor) {
                $this->auditLogService->log(
                    $actor,
                    'device.credentials_rotated',
                    DeviceIntegration::class,
                    (string) $integration->integration_id,
                    $oldStatus,
                    [
                        'device_id' => $device->device_id,
                        'platform' => $platform,
                        'is_active' => true,
                    ]
                );
            }

            return [
                'device_id' => $device->device_id,
                'integration_id' => $integration->integration_id,
                'platform' => $platform,
                'device_token' => $rawToken,
            ];
        });
    }

    public function revokeCredentials(Device $device, string $platform = 'osafe_tracker', ?Model $actor = null): bool
    {
        return DB::transaction(function () use ($device, $platform, $actor) {
            $integration = DeviceIntegration::where('device_id', $device->device_id)
                ->where('platform', $platform)
                ->first();

            if (!$integration) {
                return false;
            }

            $oldStatus = ['is_active' => $integration->is_active];
            $integration->update(['is_active' => false]);

            if ($actor) {
                $this->auditLogService->log(
                    $actor,
                    'device.credentials_revoked',
                    DeviceIntegration::class,
                    (string) $integration->integration_id,
                    $oldStatus,
                    [
                        'device_id' => $device->device_id,
                        'platform' => $platform,
                        'is_active' => false,
                    ]
                );
            }

            return true;
        });
    }

    public function authenticateDevice(string $rawToken): ?Device
    {
        $tokenHash = hash('sha256', $rawToken);

        $integrations = DeviceIntegration::where('is_active', true)->with('device')->get();

        foreach ($integrations as $integration) {
            if ($integration->access_token && hash_equals($integration->access_token, $tokenHash)) {
                $device = $integration->device;
                if ($device && $device->status !== DeviceStatus::Decommissioned) {
                    return $device;
                }
            }
        }

        return null;
    }
}
