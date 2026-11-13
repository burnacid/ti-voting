<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Only admins log in, there are no other user accounts
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // The app never changes locked properties itself, so this is a crafted or replayed Livewire request.
        // Log one line and refuse it instead of logging a server error with a stack trace.
        $exceptions->report(function (CannotUpdateLockedPropertyException $e) {
            Log::warning('Livewire request tried to change a locked property', [
                'property' => $e->property,
                'ip' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 250, ''),
            ]);

            return false;
        });
        $exceptions->render(fn (CannotUpdateLockedPropertyException $e) => response('Forbidden', 403));
    })->create();
