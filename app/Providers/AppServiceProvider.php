<?php

namespace App\Providers;

use App\Domain\Reporting\Contracts\ReportExportWriter;
use App\Domain\Reporting\Export\CsvReportExportWriter;
use App\Domain\Reporting\Export\ReportExportWriterRegistry;
use App\Domain\Reporting\Operations\ActionQueueProviderRegistry;
use App\Domain\Reporting\Operations\CodOperationsActionQueueProvider;
use App\Domain\Reporting\Operations\InventoryOperationsActionQueueProvider;
use App\Domain\Reporting\Operations\OperationalExportRegistry;
use App\Domain\Reporting\Operations\OperationalExportRegistryFactory;
use App\Domain\Reporting\Operations\OperationalOrdersActionQueueProvider;
use App\Domain\Seo\Sitemap\Data\SitemapSplitPolicy;
use App\Domain\Seo\Sitemap\Services\SitemapRegistry;
use App\Domain\Seo\Sitemap\Sources\BrandSitemapSource;
use App\Domain\Seo\Sitemap\Sources\CategorySitemapSource;
use App\Domain\Seo\Sitemap\Sources\PageSitemapSource;
use App\Domain\Seo\Sitemap\Sources\ProductSitemapSource;
use App\Domain\Shipping\Providers\ManualShippingProvider;
use App\Domain\Shipping\Providers\PathaoShippingProvider;
use App\Domain\Shipping\Providers\ShippingProviderRegistry;
use App\Domain\Shipping\Providers\SteadfastShippingProvider;
use App\Domain\Shipping\Services\PathaoShippingProviderFactory;
use App\Domain\Shipping\Services\SteadfastShippingProviderFactory;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\SeoMeta;
use App\Observers\SitemapEntityObserver;
use App\Observers\SitemapSeoMetaObserver;
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

        $this->app->tag(
            [
                CsvReportExportWriter::class,
            ],
            ReportExportWriter::class,
        );

        $this->app->when(
            ReportExportWriterRegistry::class,
        )->needs(
            '$writers',
        )->giveTagged(
            ReportExportWriter::class,
        );

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

        $this->app->singleton(
            ActionQueueProviderRegistry::class,
            static function ($app): ActionQueueProviderRegistry {
                $registry =
                    new ActionQueueProviderRegistry;

                $registry->register(
                    $app->make(
                        OperationalOrdersActionQueueProvider::class,
                    ),
                );

                $registry->register(
                    $app->make(
                        CodOperationsActionQueueProvider::class,
                    ),
                );

                $registry->register(
                    $app->make(
                        InventoryOperationsActionQueueProvider::class,
                    ),
                );

                return $registry;
            },
        );
        $this->app->singleton(
            OperationalExportRegistry::class,
            static fn (
                $app,
            ): OperationalExportRegistry => $app
                ->make(
                    OperationalExportRegistryFactory::class,
                )
                ->make(),
        );

        $this->app->singleton(
            SitemapRegistry::class,
            static function (Application $app): SitemapRegistry {
                $registry =
                    new SitemapRegistry;

                $registry->register(
                    $app->make(
                        ProductSitemapSource::class,
                    ),
                );

                return $registry;
            },
        );

        $this->app->singleton(
            SitemapRegistry::class,
            static function (Application $app): SitemapRegistry {
                $registry =
                    new SitemapRegistry;

                $registry->register(
                    $app->make(
                        ProductSitemapSource::class,
                    ),
                );

                $registry->register(
                    $app->make(
                        CategorySitemapSource::class,
                    ),
                );

                $registry->register(
                    $app->make(
                        PageSitemapSource::class,
                    ),
                );
                $registry->register(
                    $app->make(
                        BrandSitemapSource::class,
                    ),
                );

                return $registry;
            },
        );
        $this->app->singleton(
            SitemapSplitPolicy::class,
            static fn (): SitemapSplitPolicy => new SitemapSplitPolicy(
                maxUrlsPerFile: 45_000,
            ),
        );

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Product::observe(
            SitemapEntityObserver::class,
        );

        Category::observe(
            SitemapEntityObserver::class,
        );

        Brand::observe(
            SitemapEntityObserver::class,
        );

        Page::observe(
            SitemapEntityObserver::class,
        );
        SeoMeta::observe(
            SitemapSeoMetaObserver::class,
        );
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
