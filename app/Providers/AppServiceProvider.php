<?php

namespace App\Providers;

use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Events\BookingUpdated;
use App\Listeners\SendBookingLifecycleNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen([
            BookingCreated::class,
            BookingUpdated::class,
            BookingCancelled::class,
        ], SendBookingLifecycleNotification::class);

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute((int) config('booking.api_rate_per_minute', 120))
                ->by($request->user()?->getAuthIdentifier() ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute((int) config('booking.auth_rate_per_minute', 10))->by($request->ip());
        });
    }
}
