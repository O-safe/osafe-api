<?php

namespace App\Policies;

use App\Models\Admin\Staff;
use App\Models\Support\SupportTicket;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class SupportTicketPolicy
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
            return $actor->hasPermissionTo('view support tickets', 'admin');
        }
        return true;
    }

    public function view(Model $actor, SupportTicket $ticket): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('view support tickets', 'admin');
        }

        if ($actor instanceof User) {
            return $ticket->user_id === $actor->user_id;
        }

        return false;
    }

    public function update(Model $actor, SupportTicket $ticket): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('manage support tickets', 'admin') ||
                $ticket->assigned_to === $actor->staff_id;
        }

        if ($actor instanceof User) {
            return $ticket->user_id === $actor->user_id;
        }

        return false;
    }

    public function respond(Model $actor, SupportTicket $ticket): bool
    {
        if ($actor instanceof Staff) {
            return $actor->hasPermissionTo('respond to support tickets', 'admin') ||
                $ticket->assigned_to === $actor->staff_id;
        }

        if ($actor instanceof User) {
            return $ticket->user_id === $actor->user_id;
        }

        return false;
    }
}
