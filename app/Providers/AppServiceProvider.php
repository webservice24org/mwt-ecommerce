<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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
        RateLimiter::for(
            'admin-login',
            function (Request $request): Limit {
                return Limit::perMinute(5)
                    ->by(
                        strtolower(
                            (string) $request->input('email'),
                        ).'|'.$request->ip(),
                    );
            },
        );

        RateLimiter::for(
            'customer-login',
            function (Request $request): Limit {
                return Limit::perMinute(5)
                    ->by(
                        strtolower(
                            (string) $request->input('email'),
                        ).'|'.$request->ip(),
                    );
            },
        );
        Vite::prefetch(concurrency: 3);
    }
}
