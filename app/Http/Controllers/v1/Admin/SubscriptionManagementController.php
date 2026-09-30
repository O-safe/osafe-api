<?php

namespace App\Http\Controllers\v1\Admin;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\AdminStorePlanRequest;
use App\Http\Requests\Subscription\AdminUpdatePlanRequest;
use App\Http\Resources\Subscription\SubscriptionPlanResource;
use App\Http\Resources\Subscription\UserSubscriptionResource;
use App\Models\Subscription\SubscriptionPlan;
use App\Models\Subscription\UserSubscription;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionManagementController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function indexPlans(Request $request): JsonResponse
    {
        $plans = SubscriptionPlan::orderBy('price')->get();

        return response()->json([
            'success' => true,
            'message' => 'Subscription plans retrieved successfully.',
            'data' => SubscriptionPlanResource::collection($plans),
        ], 200);
    }

    public function showPlan(SubscriptionPlan $plan): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Subscription plan details retrieved successfully.',
            'data' => new SubscriptionPlanResource($plan),
        ], 200);
    }

    public function storePlan(AdminStorePlanRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan created successfully.',
            'data' => new SubscriptionPlanResource($plan),
        ], 201);
    }

    public function updatePlan(AdminUpdatePlanRequest $request, SubscriptionPlan $plan): JsonResponse
    {
        $plan->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan updated successfully.',
            'data' => new SubscriptionPlanResource($plan->fresh()),
        ], 200);
    }

    public function togglePlanStatus(SubscriptionPlan $plan): JsonResponse
    {
        $plan->update(['is_active' => !$plan->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan status toggled successfully.',
            'data' => new SubscriptionPlanResource($plan->fresh()),
        ], 200);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $query = UserSubscription::with(['user', 'plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->latest('created_at')->paginate($request->integer('per_page', 30));

        return response()->json([
            'success' => true,
            'message' => 'User subscriptions retrieved successfully.',
            'data' => UserSubscriptionResource::collection($subscriptions->items()),
            'pagination' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
        ], 200);
    }

    public function showSubscription(UserSubscription $subscription): JsonResponse
    {
        $this->authorize('view', $subscription);

        return response()->json([
            'success' => true,
            'message' => 'User subscription details retrieved successfully.',
            'data' => new UserSubscriptionResource($subscription->load(['user', 'plan'])),
        ], 200);
    }

    public function cancelSubscription(Request $request, UserSubscription $subscription): JsonResponse
    {
        $this->authorize('manage', $subscription);

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
            'auto_renew' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User subscription cancelled by admin successfully.',
            'data' => new UserSubscriptionResource($subscription->fresh(['user', 'plan'])),
        ], 200);
    }
}
