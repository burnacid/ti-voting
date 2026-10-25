<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Spatie\LaravelPasskeys\Events\PasskeyRegisteredEvent;
use Spatie\LaravelPasskeys\Events\PasskeyUsedToAuthenticateEvent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Admin logins with a passkey, logged like password logins
        Event::listen(function (PasskeyUsedToAuthenticateEvent $event) {
            Log::info('Admin logged in', ['email' => $event->passkey->authenticatable?->email, 'ip' => request()->ip(), 'method' => 'passkey', 'passkey' => $event->passkey->name]);
        });

        Event::listen(function (PasskeyRegisteredEvent $event) {
            Log::info('Admin added a passkey', ['admin' => $event->passkey->authenticatable?->email, 'passkey' => $event->passkey->name]);
        });
    }
}
