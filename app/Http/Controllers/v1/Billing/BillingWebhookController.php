<?php

namespace App\Http\Controllers\v1\Billing;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingWebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $event = $this->paymentService->handleWebhook($request);

        return response()->json([
            'success' => true,
            'message' => 'Webhook received and processed successfully.',
            'data' => [
                'event_id' => $event->event_id,
                'status' => $event->status,
                'processed_at' => $event->processed_at?->toIso8601String(),
            ],
        ], 200);
    }
}
