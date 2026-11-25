<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Merchant;

use App\Domain\Seo\Merchant\Data\MerchantFeedExportSnapshotData;
use App\Jobs\Seo\Merchant\GenerateMerchantFeedExport;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Tests\TestCase;

final class MerchantM4ReleaseSourceContractTest extends TestCase
{
    public function test_all_named_merchant_admin_routes_remain_inside_admin_surface_and_download_is_throttled(): void
    {
        $routes =
            app(
                Router::class,
            )->getRoutes();

        $download =
            $routes->getByName(
                'admin.seo.merchant.exports.download',
            );

        self::assertInstanceOf(
            Route::class,
            $download,
        );

        self::assertContains(
            'throttle:30,1',
            $download->gatherMiddleware(),
        );

        foreach ($routes as $route) {
            $name =
                $route->getName();

            if (
                ! is_string($name)
                || ! str_starts_with(
                    $name,
                    'admin.seo.merchant.',
                )
            ) {
                continue;
            }

            self::assertStringStartsWith(
                'admin/',
                $route->uri(),
            );
        }
    }

    public function test_export_contract_remains_private_bounded_unique_and_has_no_public_url_generation(): void
    {
        $job =
            new GenerateMerchantFeedExport(
                123,
            );

        self::assertInstanceOf(
            ShouldQueue::class,
            $job,
        );

        self::assertInstanceOf(
            ShouldBeUnique::class,
            $job,
        );

        self::assertSame(
            [10, 60, 300],
            $job->backoff(),
        );

        self::assertSame(
            'merchant-tsv-v1',
            MerchantFeedExportSnapshotData::SCHEMA_VERSION,
        );

        $download =
            file_get_contents(
                app_path(
                    'Domain/Seo/Merchant/Services/MerchantFeedExportDownload.php',
                ),
            );

        self::assertIsString(
            $download,
        );

        self::assertStringContainsString(
            'readStream',
            $download,
        );

        self::assertStringContainsString(
            'hash_equals',
            $download,
        );

        self::assertStringNotContainsString(
            'Storage::url',
            $download,
        );

        self::assertStringNotContainsString(
            'temporaryUrl',
            $download,
        );
    }

    public function test_release_history_does_not_expose_storage_location_and_stale_snapshot_retry_is_domain_guarded(): void
    {
        $history =
            file_get_contents(
                app_path(
                    'Domain/Seo/Merchant/Queries/GetMerchantFeedExportHistoryQuery.php',
                ),
            );

        $lifecycle =
            file_get_contents(
                app_path(
                    'Domain/Seo/Merchant/Services/MerchantFeedExportLifecycle.php',
                ),
            );

        self::assertIsString(
            $history,
        );

        self::assertIsString(
            $lifecycle,
        );

        self::assertStringNotContainsString(
            "'file_path' =>",
            $history,
        );

        self::assertStringNotContainsString(
            "'file_disk' =>",
            $history,
        );

        self::assertStringContainsString(
            "'snapshot_changed'",
            $lifecycle,
        );

        self::assertStringContainsString(
            'must be replaced by a new export',
            $lifecycle,
        );
    }

    public function test_operations_ui_keeps_google_submission_and_public_feed_out_of_scope(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Seo/Merchant/Readiness.tsx',
                ),
            );

        self::assertIsString(
            $source,
        );

        self::assertStringContainsString(
            'Export TSV',
            $source,
        );

        self::assertStringNotContainsString(
            'Merchant API',
            $source,
        );

        self::assertStringNotContainsString(
            'Submit to Google',
            $source,
        );
    }
}
