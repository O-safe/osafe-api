<?php

namespace App\Policies;

use App\Models\Admin\Staff;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class ReportPolicy
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
            return $actor->hasPermissionTo('view reports', 'admin');
        }
        return true; // Users can view basic device summary reports
    }

    public function generate(Model $actor): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('generate reports', 'admin');
        }
        return true;
    }

    public function export(Model $actor): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('export reports', 'admin');
        }
        return true;
    }
}
