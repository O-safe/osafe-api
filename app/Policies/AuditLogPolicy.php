<?php

namespace App\Policies;

use App\Models\Admin\Staff;
use Illuminate\Database\Eloquent\Model;

class AuditLogPolicy
{
    public function before(Model $actor, string $ability): ?bool
    {
        if ($actor instanceof Staff && $actor->hasRole('Super Admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(Model $actor): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('view audit logs', 'admin');
        }
        return false; // Users cannot view administrative audit logs
    }
}
