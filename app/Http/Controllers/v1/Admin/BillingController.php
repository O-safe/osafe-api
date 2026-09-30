<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subscription\BillingTransactionResource;
use App\Http\Resources\Subscription\PaymentMethodResource;
use App\Models\Subscription\BillingTransaction;
use App\Models\Subscription\PaymentMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function indexTransactions(Request $request): JsonResponse
    {
        $query = BillingTransaction::with(['user', 'subscription']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('reference')) {
            $query->where('reference', 'like', "%{$request->reference}%");
        }

        $transactions = $query->latest('transaction_date')->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'Billing transactions retrieved successfully.',
            'data' => BillingTransactionResource::collection($transactions->items()),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ], 200);
    }

    public function showTransaction(BillingTransaction $transaction): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Billing transaction details retrieved successfully.',
            'data' => new BillingTransactionResource($transaction->load(['user', 'subscription'])),
        ], 200);
    }

    public function paymentMethods(Request $request): JsonResponse
    {
        $query = PaymentMethod::with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $methods = $query->latest()->paginate($request->integer('per_page', 30));

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
}
