<?php

use App\Http\Middleware\EnsureAdminHasRole;
use App\Http\Middleware\EnsureAdminIsActive;
use App\Http\Middleware\EnsureCustomerIsActive;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(
    basePath: dirname(__DIR__)
)
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->prefix('account')
                ->name('customer.')
                ->group(base_path('routes/customer.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'customer.active' => EnsureCustomerIsActive::class,
        ]);

        $middleware->redirectGuestsTo(
            fn (Request $request): string => $request->routeIs('admin.*')
                ? route('admin.login')
                : route('login'),
        );

        $middleware->alias([
            'customer.active' => EnsureCustomerIsActive::class,
            'admin.role' => EnsureAdminHasRole::class,
        ]);

        $middleware->alias([
            'customer.active' => EnsureCustomerIsActive::class,
            'admin.active' => EnsureAdminIsActive::class,
            'admin.role' => EnsureAdminHasRole::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
