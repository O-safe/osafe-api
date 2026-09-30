<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\Integration\DeviceIntegrationProviderInterface::class,
            \App\Services\Integration\NullDeviceIntegrationProvider::class
        );

        $this->app->bind(
            \App\Services\Payment\Contracts\PaymentProviderInterface::class,
            \App\Services\Payment\NullPaymentProvider::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app['router']->aliasMiddleware(
            'auth.device',
            \App\Http\Middleware\AuthenticatePhysicalDeviceMiddleware::class
        );

        Model::preventLazyLoading(!app()->isProduction());

        \Illuminate\Support\Facades\Gate::policy(\App\Models\Device\Device::class, \App\Policies\DevicePolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Family\Family::class, \App\Policies\FamilyPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Family\FamilyMember::class, \App\Policies\FamilyMemberPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Geofence\Geofence::class, \App\Policies\GeofencePolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Subscription\UserSubscription::class, \App\Policies\UserSubscriptionPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Notification\Alert::class, \App\Policies\AlertPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Support\SupportTicket::class, \App\Policies\SupportTicketPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\System\AuditLog::class, \App\Policies\AuditLogPolicy::class);

        // Domain Event Listeners
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\AlertCreated::class,
            \App\Listeners\SendAlertNotificationListener::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\DeviceStatusChanged::class,
            \App\Listeners\SendDeviceStatusNotificationListener::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\FamilyMemberAdded::class,
            \App\Listeners\SendFamilyMemberNotificationListener::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\FamilyMemberRemoved::class,
            \App\Listeners\SendFamilyMemberNotificationListener::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\FamilyMemberRoleChanged::class,
            \App\Listeners\SendFamilyMemberNotificationListener::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\SubscriptionActivated::class,
            \App\Listeners\SendSubscriptionNotificationListener::class
        );
        \Illuminate\Support\Facades\Event::listen(
            \App\Events\SubscriptionCancelled::class,
            \App\Listeners\SendSubscriptionNotificationListener::class
        );
    }
}
