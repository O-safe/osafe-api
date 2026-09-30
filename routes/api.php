<?php

use App\Http\Controllers\v1\Admin\ActivityLogController;
use App\Http\Controllers\v1\Admin\AdminController;
use App\Http\Controllers\v1\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\v1\Admin\Auth\AdminAuthController;
use App\Http\Controllers\v1\Admin\BillingController as AdminBillingController;
use App\Http\Controllers\v1\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\v1\Admin\DeviceCredentialAdminController;
use App\Http\Controllers\v1\Admin\DeviceManagementController as AdminDeviceController;
use App\Http\Controllers\v1\DeviceIntegration\DeviceIntegrationIngestionController;
use App\Http\Controllers\v1\Admin\FamilyManagementController as AdminFamilyController;
use App\Http\Controllers\v1\Admin\ReportController as AdminReportController;
use App\Http\Controllers\v1\Admin\RoleController;
use App\Http\Controllers\v1\Admin\StaffPassportController;
use App\Http\Controllers\v1\Admin\SubscriptionManagementController as AdminSubscriptionController;
use App\Http\Controllers\v1\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\v1\Admin\SystemHealthController as AdminSystemHealthController;
use App\Http\Controllers\v1\Admin\UserManagementController;
use App\Http\Controllers\v1\Admin\UserPassportController;
use App\Http\Controllers\v1\Setup\CountryController;
use App\Http\Controllers\v1\Setup\GenderController;
use App\Http\Controllers\v1\Setup\LgaController;
use App\Http\Controllers\v1\Setup\MeansOfIdentificationController;
use App\Http\Controllers\v1\Setup\StateController;
use App\Http\Controllers\v1\Setup\StatusController;
use App\Http\Controllers\v1\Setup\TitleController;
use App\Http\Controllers\v1\Billing\BillingWebhookController;
use App\Http\Controllers\v1\User\AlertController as UserAlertController;
use App\Http\Controllers\v1\User\Auth\UserAuthController;
use App\Http\Controllers\v1\User\BillingController as UserBillingController;
use App\Http\Controllers\v1\User\DashboardController as UserDashboardController;
use App\Http\Controllers\v1\User\DeviceController as UserDeviceController;
use App\Http\Controllers\v1\User\FamilyController as UserFamilyController;
use App\Http\Controllers\v1\User\GeofenceController as UserGeofenceController;
use App\Http\Controllers\v1\User\LocationController as UserLocationController;
use App\Http\Controllers\v1\User\NotificationController as UserNotificationController;
use App\Http\Controllers\v1\User\SubscriptionController as UserSubscriptionController;
use App\Http\Controllers\v1\User\SupportController as UserSupportController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

    // =====================================================================
    // ADMIN ROUTES
    // =====================================================================
    Route::prefix('admin')->group(function () {

        // Public admin auth routes (throttled)
        Route::prefix('auth')->controller(AdminAuthController::class)->group(function () {
            Route::post('login', 'login')->middleware('throttle:5,1');
            Route::post('verify-login-otp', 'verifyOtp')->middleware('throttle:5,1');
            Route::post('reset-password', 'resetPassword')->middleware('throttle:5,1');
            Route::post('resend-mail', 'resendPasswordResetLink')->middleware('throttle:5,1');
            Route::post('finish-reset-password', 'finishResetPassword')->middleware('throttle:5,1');
        });

        // Public password change completion (token-based, no session required)
        Route::post('finish-change-password', [AdminAuthController::class, 'finishChangePassword'])->middleware('throttle:5,1');

        // Protected admin routes
        Route::middleware(['auth:admin', 'trust.device'])->group(function () {
            Route::post('change-password', [AdminAuthController::class, 'changePassword'])->middleware('throttle:5,1');
            Route::get('fetch-profile', [AdminAuthController::class, 'fetchProfile']);
            Route::post('logout', [AdminAuthController::class, 'logout']);

            // Dashboard
            Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('v1.admin.dashboard');

            // Staff management (protected)
            Route::apiResource('staff', AdminController::class);
            Route::post('staff-passport/{id}', [StaffPassportController::class, 'update']);
            Route::post('user-passport/{id}', [UserPassportController::class, 'update']);

            // User management
            Route::apiResource('users', UserManagementController::class)->middleware('permission:manage users');

            // Device management
            Route::get('devices', [AdminDeviceController::class, 'index'])->name('v1.admin.devices.index');
            Route::post('devices', [AdminDeviceController::class, 'store'])->name('v1.admin.devices.store');
            Route::get('devices/{device}', [AdminDeviceController::class, 'show'])->name('v1.admin.devices.show');
            Route::put('devices/{device}', [AdminDeviceController::class, 'update'])->name('v1.admin.devices.update');
            Route::post('devices/{device}/activate', [AdminDeviceController::class, 'activate'])->name('v1.admin.devices.activate');
            Route::post('devices/{device}/deactivate', [AdminDeviceController::class, 'deactivate'])->name('v1.admin.devices.deactivate');
            Route::post('devices/{device}/assign', [AdminDeviceController::class, 'assign'])->name('v1.admin.devices.assign');
            Route::post('devices/{device}/reassign', [AdminDeviceController::class, 'reassign'])->name('v1.admin.devices.reassign');
            Route::post('devices/{device}/revoke-assignment', [AdminDeviceController::class, 'revokeAssignment'])->name('v1.admin.devices.revoke-assignment');
            Route::post('devices/{device}/credentials/issue', [DeviceCredentialAdminController::class, 'issue'])->name('v1.admin.devices.credentials.issue');
            Route::post('devices/{device}/credentials/rotate', [DeviceCredentialAdminController::class, 'rotate'])->name('v1.admin.devices.credentials.rotate');
            Route::post('devices/{device}/credentials/revoke', [DeviceCredentialAdminController::class, 'revoke'])->name('v1.admin.devices.credentials.revoke');

            // Family management
            Route::get('families', [AdminFamilyController::class, 'index'])->name('v1.admin.families.index');
            Route::post('families', [AdminFamilyController::class, 'store'])->name('v1.admin.families.store');
            Route::get('families/{family}', [AdminFamilyController::class, 'show'])->name('v1.admin.families.show');
            Route::put('families/{family}', [AdminFamilyController::class, 'update'])->name('v1.admin.families.update');
            Route::get('families/{family}/members', [AdminFamilyController::class, 'members'])->name('v1.admin.families.members');
            Route::post('families/{family}/members', [AdminFamilyController::class, 'addMember'])->name('v1.admin.families.members.add');
            Route::delete('families/{family}/members/{userId}', [AdminFamilyController::class, 'removeMember'])->name('v1.admin.families.members.remove');
            Route::put('families/{family}/members/{userId}/role', [AdminFamilyController::class, 'changeRole'])->name('v1.admin.families.members.role');
            Route::post('families/{family}/invite', [AdminFamilyController::class, 'invite'])->name('v1.admin.families.invite');

            // Subscription management
            Route::get('plans', [AdminSubscriptionController::class, 'indexPlans'])->name('v1.admin.plans.index');
            Route::post('plans', [AdminSubscriptionController::class, 'storePlan'])->name('v1.admin.plans.store');
            Route::get('plans/{plan}', [AdminSubscriptionController::class, 'showPlan'])->name('v1.admin.plans.show');
            Route::put('plans/{plan}', [AdminSubscriptionController::class, 'updatePlan'])->name('v1.admin.plans.update');
            Route::post('plans/{plan}/toggle-status', [AdminSubscriptionController::class, 'togglePlanStatus'])->name('v1.admin.plans.toggle-status');
            Route::get('subscriptions', [AdminSubscriptionController::class, 'subscriptions'])->name('v1.admin.subscriptions.index');
            Route::get('subscriptions/{subscription}', [AdminSubscriptionController::class, 'showSubscription'])->name('v1.admin.subscriptions.show');
            Route::post('subscriptions/{subscription}/cancel', [AdminSubscriptionController::class, 'cancelSubscription'])->name('v1.admin.subscriptions.cancel');

            // Billing
            Route::get('billing/transactions', [AdminBillingController::class, 'indexTransactions'])->name('v1.admin.billing.transactions.index');
            Route::get('billing/transactions/{transaction}', [AdminBillingController::class, 'showTransaction'])->name('v1.admin.billing.transactions.show');
            Route::get('billing/payment-methods', [AdminBillingController::class, 'paymentMethods'])->name('v1.admin.billing.payment-methods.index');

            // Support management
            Route::get('support/tickets', [AdminSupportController::class, 'index'])->name('v1.admin.support.tickets.index');
            Route::get('support/tickets/{ticket}', [AdminSupportController::class, 'show'])->name('v1.admin.support.tickets.show');
            Route::post('support/tickets/{ticket}/assign', [AdminSupportController::class, 'assign'])->name('v1.admin.support.tickets.assign');
            Route::post('support/tickets/{ticket}/messages', [AdminSupportController::class, 'respond'])->name('v1.admin.support.tickets.messages.store');
            Route::post('support/tickets/{ticket}/resolve', [AdminSupportController::class, 'resolve'])->name('v1.admin.support.tickets.resolve');

            // Audit logs
            Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('v1.admin.audit-logs.index');
            Route::get('audit-logs/{auditLog}', [AdminAuditLogController::class, 'show'])->name('v1.admin.audit-logs.show');

            // Reports
            Route::get('reports/types', [AdminReportController::class, 'types'])->name('v1.admin.reports.types');
            Route::post('reports/generate', [AdminReportController::class, 'generate'])->name('v1.admin.reports.generate')->middleware('throttle:5,1');
            Route::get('reports/{report}', [AdminReportController::class, 'show'])->name('v1.admin.reports.show');

            // System health
            Route::get('system/health', [AdminSystemHealthController::class, 'status'])->name('v1.admin.system.health');
            Route::get('system/checks', [AdminSystemHealthController::class, 'checks'])->name('v1.admin.system.checks');
            Route::get('system/incidents', [AdminSystemHealthController::class, 'incidents'])->name('v1.admin.system.incidents');

            // Roles & permissions
            Route::apiResource('role', RoleController::class);
            Route::get('permissions', [RoleController::class, 'permissions']);

            // Legacy Activity logs
            Route::get('dashboard-metrics', [ActivityLogController::class, 'dashboardMetrics']);
            Route::get('dashboard-chart', [ActivityLogController::class, 'dashboardChart']);
            Route::get('activity-logs', [ActivityLogController::class, 'index']);
            Route::get('activity-logs/search', [ActivityLogController::class, 'search']);
            Route::get('activity-logs/unread-count', [ActivityLogController::class, 'unreadCount']);
            Route::post('activity-logs/mark-all-read', [ActivityLogController::class, 'markAllAsRead']);
            Route::get('activity-logs/{id}', [ActivityLogController::class, 'show']);
            Route::post('activity-logs/{id}/read', [ActivityLogController::class, 'markAsRead']);
            Route::get('activity-logs/{id}/read-by', [ActivityLogController::class, 'readBy']);
        });
    });

    // =====================================================================
    // USER ROUTES
    // =====================================================================
    Route::prefix('user')->group(function () {

        // Public user auth routes (throttled)
        Route::prefix('auth')->controller(UserAuthController::class)->group(function () {
            Route::post('login', 'login')->middleware('throttle:5,1');
            Route::post('verify-login-otp', 'verifyOtp')->middleware('throttle:5,1');
            Route::post('reset-password', 'resetPassword')->middleware('throttle:5,1');
            Route::post('resend-mail', 'resendPasswordResetLink')->middleware('throttle:5,1');
            Route::post('finish-reset-password', 'finishResetPassword')->middleware('throttle:5,1');
        });

        // Protected user routes
        Route::middleware(['auth:user', 'trust.device'])->group(function () {
            Route::post('logout', [UserAuthController::class, 'logout']);
            Route::get('user-profile', [UserAuthController::class, 'fetchProfile']);
            Route::post('change-password', [UserAuthController::class, 'changePassword']);
            Route::put('update/{id}', [UserManagementController::class, 'update']);
            Route::post('user-passport/{id}', [UserPassportController::class, 'update']);

            // Dashboard
            Route::get('dashboard', [UserDashboardController::class, 'index'])->name('v1.user.dashboard');

            // Devices
            Route::get('devices', [UserDeviceController::class, 'index'])->name('v1.user.devices.index');
            Route::get('devices/{device}', [UserDeviceController::class, 'show'])->name('v1.user.devices.show');
            Route::get('devices/{device}/status', [UserDeviceController::class, 'status'])->name('v1.user.devices.status');
            Route::get('devices/{device}/assignment', [UserDeviceController::class, 'assignment'])->name('v1.user.devices.assignment');
            Route::post('devices/{device}/commands', [UserDeviceController::class, 'storeCommand'])->name('v1.user.devices.commands.store')->middleware('throttle:10,1');

            // Location
            Route::get('devices/{device}/location/current', [UserLocationController::class, 'current'])->name('v1.user.location.current');
            Route::get('devices/{device}/location/history', [UserLocationController::class, 'history'])->name('v1.user.location.history');
            Route::get('devices/{device}/location/events', [UserLocationController::class, 'events'])->name('v1.user.location.events');

            // Families
            Route::get('families', [UserFamilyController::class, 'index'])->name('v1.user.families.index');
            Route::post('families', [UserFamilyController::class, 'store'])->name('v1.user.families.store');
            Route::get('families/{family}', [UserFamilyController::class, 'show'])->name('v1.user.families.show');
            Route::put('families/{family}', [UserFamilyController::class, 'update'])->name('v1.user.families.update');
            Route::get('families/{family}/members', [UserFamilyController::class, 'members'])->name('v1.user.families.members');
            Route::post('families/{family}/members', [UserFamilyController::class, 'addMember'])->name('v1.user.families.members.add');
            Route::delete('families/{family}/members/{userId}', [UserFamilyController::class, 'removeMember'])->name('v1.user.families.members.remove');
            Route::put('families/{family}/members/{userId}/role', [UserFamilyController::class, 'changeRole'])->name('v1.user.families.members.role');
            Route::post('families/{family}/invite', [UserFamilyController::class, 'invite'])->name('v1.user.families.invite');

            // Geofences
            Route::get('geofences', [UserGeofenceController::class, 'index'])->name('v1.user.geofences.index');
            Route::post('geofences', [UserGeofenceController::class, 'store'])->name('v1.user.geofences.store');
            Route::get('geofences/{geofence}', [UserGeofenceController::class, 'show'])->name('v1.user.geofences.show');
            Route::put('geofences/{geofence}', [UserGeofenceController::class, 'update'])->name('v1.user.geofences.update');
            Route::delete('geofences/{geofence}', [UserGeofenceController::class, 'destroy'])->name('v1.user.geofences.destroy');
            Route::post('geofences/{geofence}/attach-device', [UserGeofenceController::class, 'attachDevice'])->name('v1.user.geofences.attach-device');
            Route::delete('geofences/{geofence}/devices/{device}', [UserGeofenceController::class, 'detachDevice'])->name('v1.user.geofences.detach-device');
            Route::get('geofences/{geofence}/events', [UserGeofenceController::class, 'events'])->name('v1.user.geofences.events');

            // Alerts
            Route::get('alerts', [UserAlertController::class, 'index'])->name('v1.user.alerts.index');
            Route::get('alerts/{alert}', [UserAlertController::class, 'show'])->name('v1.user.alerts.show');
            Route::post('alerts/{alert}/read', [UserAlertController::class, 'markRead'])->name('v1.user.alerts.mark-read');
            Route::post('alerts/{alert}/dismiss', [UserAlertController::class, 'dismiss'])->name('v1.user.alerts.dismiss');
            Route::post('alerts/{alert}/resolve', [UserAlertController::class, 'resolve'])->name('v1.user.alerts.resolve');

            // Notifications
            Route::get('notifications', [UserNotificationController::class, 'index'])->name('v1.user.notifications.index');
            Route::post('notifications/{id}/read', [UserNotificationController::class, 'markRead'])->name('v1.user.notifications.mark-read');
            Route::get('notification-preferences', [UserNotificationController::class, 'preferences'])->name('v1.user.notification-preferences.index');
            Route::put('notification-preferences', [UserNotificationController::class, 'updatePreferences'])->name('v1.user.notification-preferences.update');

            // Subscriptions & Billing
            Route::get('subscriptions/current', [UserSubscriptionController::class, 'current'])->name('v1.user.subscriptions.current');
            Route::get('subscriptions/history', [UserSubscriptionController::class, 'history'])->name('v1.user.subscriptions.history');
            Route::get('subscriptions/plans', [UserSubscriptionController::class, 'plans'])->name('v1.user.subscriptions.plans');
            Route::post('subscriptions/checkout', [UserSubscriptionController::class, 'checkout'])->name('v1.user.subscriptions.checkout')->middleware('throttle:10,1');
            Route::get('subscriptions/{subscription}', [UserSubscriptionController::class, 'show'])->name('v1.user.subscriptions.show');
            Route::post('subscriptions/{subscription}/cancel', [UserSubscriptionController::class, 'cancel'])->name('v1.user.subscriptions.cancel');
            Route::get('billing/transactions', [UserBillingController::class, 'transactions'])->name('v1.user.billing.transactions.index');
            Route::get('billing/transactions/{transaction}', [UserBillingController::class, 'showTransaction'])->name('v1.user.billing.transactions.show');
            Route::get('billing/payment-methods', [UserBillingController::class, 'paymentMethods'])->name('v1.user.billing.payment-methods.index');
            Route::post('billing/verify/{reference}', [UserBillingController::class, 'verify'])->name('v1.user.billing.verify')->middleware('throttle:10,1');

            // Support
            Route::get('support/tickets', [UserSupportController::class, 'index'])->name('v1.user.support.tickets.index');
            Route::post('support/tickets', [UserSupportController::class, 'store'])->name('v1.user.support.tickets.store')->middleware('throttle:5,1');
            Route::get('support/tickets/{ticket}', [UserSupportController::class, 'show'])->name('v1.user.support.tickets.show');
            Route::post('support/tickets/{ticket}/messages', [UserSupportController::class, 'addMessage'])->name('v1.user.support.tickets.messages.store');
            Route::post('support/tickets/{ticket}/close', [UserSupportController::class, 'close'])->name('v1.user.support.tickets.close');
        });
    });

    // =====================================================================
    // SETUP ROUTES (public reference data)
    // =====================================================================
    Route::prefix('setup')->group(function () {
        Route::get('country', [CountryController::class, 'index']);
        Route::get('state', [StateController::class, 'index']);
        Route::get('lga', [LgaController::class, 'index']);
        Route::get('gender', [GenderController::class, 'index']);
        Route::get('title', [TitleController::class, 'index']);
        Route::get('status', [StatusController::class, 'index']);
        Route::get('means-of-identification', [MeansOfIdentificationController::class, 'index']);
    });

    // =====================================================================
    // PUBLIC BILLING WEBHOOK ROUTE
    // =====================================================================
    Route::post('webhooks/billing', [BillingWebhookController::class, 'handle'])->middleware('throttle:60,1');

    // =====================================================================
    // PHYSICAL DEVICE INTEGRATION BOUNDARY ROUTES
    // =====================================================================
    Route::prefix('device-integration')->middleware(['auth.device'])->group(function () {
        Route::post('heartbeat', [DeviceIntegrationIngestionController::class, 'heartbeat'])->middleware('throttle:60,1');
        Route::post('status', [DeviceIntegrationIngestionController::class, 'status'])->middleware('throttle:30,1');
        Route::post('battery', [DeviceIntegrationIngestionController::class, 'battery'])->middleware('throttle:30,1');
        Route::post('network', [DeviceIntegrationIngestionController::class, 'network'])->middleware('throttle:30,1');
        Route::post('location', [DeviceIntegrationIngestionController::class, 'location'])->middleware('throttle:120,1');
        Route::get('commands', [DeviceIntegrationIngestionController::class, 'commands'])->middleware('throttle:60,1');
        Route::post('commands/{commandId}/ack', [DeviceIntegrationIngestionController::class, 'ackCommand'])->middleware('throttle:60,1');
    });
});
