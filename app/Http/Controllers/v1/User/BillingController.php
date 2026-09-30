<?php

namespace App\Http\Controllers\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subscription\BillingTransactionResource;
use App\Http\Resources\Subscription\PaymentMethodResource;
use App\Models\Subscription\BillingTransaction;
use App\Models\Subscription\PaymentMethod;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function transactions(Request $request): JsonResponse
    {
        $user = $request->user();

        $transactions = BillingTransaction::with(['subscription'])
            ->where('user_id', $user->user_id)
            ->latest()
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'User billing transactions retrieved successfully.',
            'data' => BillingTransactionResource::collection($transactions->items()),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ], 200);
    }

    public function showTransaction(Request $request, BillingTransaction $transaction): JsonResponse
    {
        if ($transaction->user_id !== $request->user()->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to billing transaction.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Billing transaction details retrieved successfully.',
            'data' => new BillingTransactionResource($transaction->load(['subscription'])),
        ], 200);
    }

    public function paymentMethods(Request $request): JsonResponse
    {
        $user = $request->user();

        $methods = PaymentMethod::where('user_id', $user->user_id)
            ->where('is_active', true)
            ->latest()
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'Payment methods retrieved successfully.',
            'data' => PaymentMethodResource::collection($methods->items()),
            'pagination' => [
                'current_page' => $methods->currentPage(),
                'last_page' => $methods->lastPage(),
                'per_page' => $methods->perPage(),
                'total' => $methods->total(),
            ],
        ], 200);
    }

    public function verify(Request $request, string $reference): JsonResponse
    {
        $transaction = BillingTransaction::where('reference', $reference)->firstOrFail();

        if ($transaction->user_id !== $request->user()->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to billing transaction.',
            ], 403);
        }

        $processedTransaction = $this->paymentService->verifyAndProcessPayment($reference);

        return response()->json([
            'success' => true,
            'message' => 'Payment status verified successfully.',
            'data' => new BillingTransactionResource($processedTransaction),
        ], 200);
    }
}
