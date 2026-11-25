<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Performance;

use App\Domain\Seo\Performance\Data\SeoPerformanceFetchRequestData;
use App\Domain\Seo\Performance\Enums\SeoPerformanceConnectionStatus;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Providers\GoogleAnalyticsProviderAdapter;
use App\Domain\Seo\Performance\Services\SeoPerformanceConnectionManager;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class GoogleAnalyticsLandingPageFetchTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_connection_fetches_bounded_landing_page_rows(): void
    {
        $this->configureCredentials();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'ga-test-token',
                'token_type' => 'Bearer',
            ]),
            'https://analyticsdata.googleapis.com/v1beta/properties/123456789:runReport' => Http::response([
                'dimensionHeaders' => [
                    ['name' => 'date'],
                    ['name' => 'landingPage'],
                ],
                'metricHeaders' => [
                    ['name' => 'sessions'],
                    ['name' => 'engagedSessions'],
                    ['name' => 'activeUsers'],
                    ['name' => 'newUsers'],
                    ['name' => 'keyEvents'],
                    ['name' => 'totalRevenue'],
                ],
                'rows' => [
                    [
                        'dimensionValues' => [
                            ['value' => '20261120'],
                            ['value' => '/products/red-shoes?ref=organic'],
                        ],
                        'metricValues' => [
                            ['value' => '12'],
                            ['value' => '9'],
                            ['value' => '10'],
                            ['value' => '4'],
                            ['value' => '2'],
                            ['value' => '1250.50'],
                        ],
                    ],
                    [
                        'dimensionValues' => [
                            ['value' => '20261120'],
                            ['value' => '/category/shoes'],
                        ],
                        'metricValues' => [
                            ['value' => '8'],
                            ['value' => '5'],
                            ['value' => '7'],
                            ['value' => '2'],
                            ['value' => '1'],
                            ['value' => '0'],
                        ],
                    ],
                ],
                'rowCount' => 3,
            ]),
        ]);

        $manager = app(SeoPerformanceConnectionManager::class);
        $connection = $manager->register(
            SeoPerformanceProvider::GoogleAnalytics,
            'GA4 Production',
            '123456789',
            null,
            'UTC',
        );
        $connection->forceFill([
            'status' => SeoPerformanceConnectionStatus::Active,
            'verified_at' => now(),
        ])->save();
        $connection = $connection->refresh();

        $page = app(GoogleAnalyticsProviderAdapter::class)->fetchPage(
            $connection,
            new SeoPerformanceFetchRequestData(
                scope: SeoPerformanceSyncScope::LandingAnalytics,
                dateFrom: new DateTimeImmutable('2026-11-20'),
                dateTo: new DateTimeImmutable('2026-11-21'),
                rowLimit: 2,
            ),
        );

        self::assertFalse($page->complete);
        self::assertFalse($page->truncated);
        self::assertSame('offset:2', $page->nextPageToken);
        self::assertCount(2, $page->rows);
        self::assertSame('2026-11-20', $page->rows[0]['date']);
        self::assertSame('/products/red-shoes?ref=organic', $page->rows[0]['landing_page']);
        self::assertSame(12.0, $page->rows[0]['sessions']);

        Http::assertSent(static function ($request): bool {
            if ($request->url() !== 'https://analyticsdata.googleapis.com/v1beta/properties/123456789:runReport') {
                return false;
            }

            return $request['limit'] === 2
                && $request['offset'] === 0
                && $request['dimensions'][0]['name'] === 'date'
                && $request['dimensions'][1]['name'] === 'landingPage'
                && $request['metrics'][0]['name'] === 'sessions'
                && $request['metrics'][5]['name'] === 'totalRevenue';
        });
    }

    private function configureCredentials(): void
    {
        $privateKey = file_get_contents(
            base_path('tests/Fixtures/Seo/Performance/google-analytics-test-private-key.pem'),
        );
        self::assertIsString($privateKey);

        $path = storage_path('framework/testing/google-analytics-service-account.json');
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, json_encode([
            'type' => 'service_account',
            'client_email' => 'analytics-test@example.iam.gserviceaccount.com',
            'private_key_id' => 'test-key',
            'private_key' => $privateKey,
        ], JSON_THROW_ON_ERROR));

        config()->set(
            'seo_performance_google_analytics.service_account_json',
            $path,
        );
    }
}
