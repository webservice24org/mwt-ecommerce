<?php

namespace App\Providers;

use App\Domain\Shipping\Providers\ManualShippingProvider;
use App\Domain\Shipping\Providers\PathaoShippingProvider;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Domain\Shipping\Providers\SteadfastShippingProvider;
use App\Domain\Shipping\Services\PathaoShippingProviderFactory;
use App\Domain\Shipping\Services\SteadfastShippingProviderFactory;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
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
        $this->app->singleton(
            ShippingProviderRegistry::class,
            static function (Application $app): ShippingProviderRegistry {
                $registry =
                    new ShippingProviderRegistry;

                /*
                 * Manual courier is always available.
                 */
                $registry->register(
                    new ManualShippingProvider,
                );

                /*
                 * Pathao is registered only when the
                 * runtime merchant account/configuration
                 * is operational.
                 */
                $pathao =
                    $app->make(
                        PathaoShippingProviderFactory::class,
                    )
                        ->runtimeProvider();

                if (
                    $pathao instanceof PathaoShippingProvider
                ) {
                    $registry->register(
                        $pathao,
                    );
                }

                /*
                 * Steadfast is registered only when its
                 * merchant account is Connected + Active.
                 */
                $steadfast =
                    $app->make(
                        SteadfastShippingProviderFactory::class,
                    )
                        ->runtimeProvider();

                if (
                    $steadfast instanceof SteadfastShippingProvider
                ) {
                    $registry->register(
                        $steadfast,
                    );
                }

                return $registry;
            },
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for(
            'admin-login',
            function (Request $request): Limit {
                return Limit::perMinute(
                    5,
                )->by(
                    strtolower(
                        (string) $request
                            ->input(
                                'email',
                            ),
                    )
                    .'|'
                    .$request->ip(),
                );
            },
        );

        RateLimiter::for(
            'customer-login',
            function (Request $request): Limit {
                return Limit::perMinute(
                    5,
                )->by(
                    strtolower(
                        (string) $request
                            ->input(
                                'email',
                            ),
                    )
                    .'|'
                    .$request->ip(),
                );
            },
        );

        Vite::prefetch(
            concurrency: 3,
        );
    }
}
