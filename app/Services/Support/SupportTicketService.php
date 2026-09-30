<?php

namespace App\Services\Support;

use App\Enums\SupportTicketStatus;
use App\Models\Admin\Staff;
use App\Models\Setup\SetupCounter;
use App\Models\Support\SupportTicket;
use App\Models\User\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SupportTicketService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function createTicket(User $user, array $data): SupportTicket
    {
        return DB::transaction(function () use ($user, $data) {
            $ticketNumber = SetupCounter::getNextFormattedNumber('TKT');

            $ticket = SupportTicket::create([
                'ticket_number' => $ticketNumber,
                'user_id' => $user->user_id,
                'device_id' => $data['device_id'] ?? null,
                'subject' => $data['subject'],
                'description' => $data['description'],
                'category' => $data['category'] ?? 'general',
                'priority' => $data['priority'] ?? 'medium',
                'status' => SupportTicketStatus::Open,
            ]);

            $this->auditLogService->log(
                $user,
                'support_ticket.created',
                SupportTicket::class,
                (string) $ticket->ticket_id,
                null,
                ['ticket_number' => $ticketNumber]
            );

            return $ticket;
        });
    }

    public function assignTicket(Staff $staff, SupportTicket $ticket): SupportTicket
    {
        $ticket->update([
            'assigned_to' => $staff->staff_id,
            'status' => SupportTicketStatus::InProgress,
        ]);

        $this->auditLogService->log(
            $staff,
            'support_ticket.assigned',
            SupportTicket::class,
            (string) $ticket->ticket_id,
            null,
            ['assigned_to' => $staff->staff_id]
        );

        return $ticket;
    }

    public function resolveTicket(Model $actor, SupportTicket $ticket, string $resolutionNote): SupportTicket
    {
        $ticket->update([
            'status' => SupportTicketStatus::Resolved,
            'resolved_at' => now(),
            'resolution_note' => $resolutionNote,
        ]);

        $this->auditLogService->log(
            $actor,
            'support_ticket.resolved',
            SupportTicket::class,
            (string) $ticket->ticket_id,
            null,
            ['resolution_note' => $resolutionNote]
        );

        return $ticket;
    }
}
