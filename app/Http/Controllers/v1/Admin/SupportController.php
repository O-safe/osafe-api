<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\AddSupportTicketMessageRequest;
use App\Http\Requests\Support\AssignSupportTicketRequest;
use App\Http\Requests\Support\ResolveSupportTicketRequest;
use App\Http\Resources\Support\SupportTicketMessageResource;
use App\Http\Resources\Support\SupportTicketResource;
use App\Models\Admin\Staff;
use App\Models\Support\SupportTicket;
use App\Models\Support\SupportTicketMessage;
use App\Services\Support\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function __construct(
        protected SupportTicketService $ticketService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SupportTicket::class);

        $query = SupportTicket::with(['user', 'device', 'assignedStaff']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $tickets = $query->latest('created_at')->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'Admin support tickets retrieved successfully.',
            'data' => SupportTicketResource::collection($tickets->items()),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ], 200);
    }

    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket details retrieved successfully.',
            'data' => new SupportTicketResource($ticket->load(['user', 'device', 'assignedStaff', 'messages'])),
        ], 200);
    }

    public function assign(AssignSupportTicketRequest $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $staff = Staff::findOrFail($request->staff_id);

        $updated = $this->ticketService->assignTicket($staff, $ticket);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket assigned successfully.',
            'data' => new SupportTicketResource($updated->load(['assignedStaff'])),
        ], 200);
    }

    public function respond(AddSupportTicketMessageRequest $request, SupportTicket $ticket): JsonResponse
    {
        $staff = $request->user();
        $this->authorize('respond', $ticket);

        $message = SupportTicketMessage::create([
            'ticket_id' => $ticket->ticket_id,
            'sender_type' => 'staff',
            'sender_id' => $staff->staff_id,
            'message' => $request->validated()['message'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Response added to support ticket successfully.',
            'data' => new SupportTicketMessageResource($message),
        ], 201);
    }

    public function resolve(ResolveSupportTicketRequest $request, SupportTicket $ticket): JsonResponse
    {
        $staff = $request->user();
        $this->authorize('update', $ticket);

        $resolved = $this->ticketService->resolveTicket($staff, $ticket, $request->resolution_note ?? 'Resolved by support officer.');

        return response()->json([
            'success' => true,
            'message' => 'Support ticket resolved successfully.',
            'data' => new SupportTicketResource($resolved),
        ], 200);
    }
}
