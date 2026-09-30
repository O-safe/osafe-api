<?php

namespace App\Http\Controllers\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\AddSupportTicketMessageRequest;
use App\Http\Requests\Support\StoreSupportTicketRequest;
use App\Http\Resources\Support\SupportTicketMessageResource;
use App\Http\Resources\Support\SupportTicketResource;
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
        $user = $request->user();

        $tickets = SupportTicket::with(['device', 'assignedStaff'])
            ->where('user_id', $user->user_id)
            ->latest('created_at')
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'Support tickets retrieved successfully.',
            'data' => SupportTicketResource::collection($tickets->items()),
            'pagination' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ], 200);
    }

    public function store(StoreSupportTicketRequest $request): JsonResponse
    {
        $user = $request->user();

        $ticket = $this->ticketService->createTicket($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created successfully.',
            'data' => new SupportTicketResource($ticket->load(['device', 'assignedStaff'])),
        ], 201);
    }

    public function show(Request $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket details retrieved successfully.',
            'data' => new SupportTicketResource($ticket->load(['device', 'assignedStaff', 'messages'])),
        ], 200);
    }

    public function addMessage(AddSupportTicketMessageRequest $request, SupportTicket $ticket): JsonResponse
    {
        $user = $request->user();
        $this->authorize('respond', $ticket);

        $message = SupportTicketMessage::create([
            'ticket_id' => $ticket->ticket_id,
            'sender_type' => 'user',
            'sender_id' => $user->user_id,
            'message' => $request->validated()['message'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message added to support ticket successfully.',
            'data' => new SupportTicketMessageResource($message),
        ], 201);
    }

    public function close(Request $request, SupportTicket $ticket): JsonResponse
    {
        $user = $request->user();
        $this->authorize('update', $ticket);

        $resolved = $this->ticketService->resolveTicket($user, $ticket, 'Closed by user.');

        return response()->json([
            'success' => true,
            'message' => 'Support ticket closed successfully.',
            'data' => new SupportTicketResource($resolved),
        ], 200);
    }
}
