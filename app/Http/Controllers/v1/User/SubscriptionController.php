<?php

namespace App\Http\Controllers\v1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subscription\BillingTransactionResource;
use App\Http\Resources\Subscription\SubscriptionPlanResource;
use App\Http\Resources\Subscription\UserSubscriptionResource;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\Subscription\UserSubscription;
use App\Services\Payment\PaymentService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected PaymentService $paymentService
    ) {}

    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $this->subscriptionService->getActiveSubscription($user);

        return response()->json([
            'success' => true,
            'message' => 'Current subscription retrieved successfully.',
            'data' => $subscription ? new UserSubscriptionResource($subscription->load('plan')) : null,
        ], 200);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscriptions = UserSubscription::with('plan')
            ->where('user_id', $user->user_id)
            ->latest('created_at')
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'Subscription history retrieved successfully.',
            'data' => UserSubscriptionResource::collection($subscriptions->items()),
            'pagination' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
        ], 200);
    }

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('price_monthly')->get();

        return response()->json([
            'success' => true,
            'message' => 'Available subscription plans retrieved successfully.',
            'data' => SubscriptionPlanResource::collection($plans),
        ], 200);
    }

    public function show(Request $request, UserSubscription $subscription): JsonResponse
    {
        $this->authorize('view', $subscription);

        return response()->json([
            'success' => true,
            'message' => 'Subscription details retrieved successfully.',
            'data' => new UserSubscriptionResource($subscription->load('plan')),
        ], 200);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,plan_id',
            'billing_cycle' => 'nullable|string|in:monthly,yearly',
        ]);

        $user = $request->user();
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $billingCycle = $validated['billing_cycle'] ?? 'monthly';

        $checkoutResult = $this->paymentService->initializeCheckout($user, $plan, $billingCycle);

        return response()->json([
            'success' => true,
            'message' => 'Subscription checkout initialized successfully.',
            'data' => [
                'transaction' => new BillingTransactionResource($checkoutResult['transaction']),
                'checkout_url' => $checkoutResult['checkout_url'],
                'reference' => $checkoutResult['reference'],
            ],
        ], 200);
    }

    public function cancel(Request $request, UserSubscription $subscription): JsonResponse
    {
        $this->authorize('manage', $subscription);

        $cancelledSubscription = $this->subscriptionService->cancelSubscription($request->user(), $subscription);

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully.',
            'data' => new UserSubscriptionResource($cancelledSubscription->load('plan')),
        ], 200);
    }
}
