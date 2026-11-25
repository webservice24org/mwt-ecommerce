<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Performance;

use App\Domain\Seo\Performance\Data\SeoPerformanceFetchRequestData;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Providers\SearchConsoleProviderAdapter;
use App\Domain\Seo\Performance\Services\SearchConsoleApiClient;
use App\Domain\Seo\Performance\Services\SearchConsoleServiceAccountAccessTokenProvider;
use App\Domain\Seo\Performance\Services\SeoPerformanceConnectionManager;
use App\Models\SeoPerformanceConnection;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class SearchConsoleSearchAnalyticsFetchTest extends TestCase
{
    use RefreshDatabase;

    private ?string $credentialFile = null;

    protected function tearDown(): void
    {
        if ($this->credentialFile !== null && is_file($this->credentialFile)) {
            @unlink($this->credentialFile);
        }

        parent::tearDown();
    }

    public function test_adapter_fetches_query_then_landing_page_rows_with_finalized_bounded_requests(): void
    {
        $this->configureCredentials();
        $property = 'sc-domain:example.com';
        $endpoint = SearchConsoleApiClient::SITES_URI.'/'.rawurlencode($property).'/searchAnalytics/query';

        Http::fake([
            SearchConsoleServiceAccountAccessTokenProvider::TOKEN_URI => Http::sequence()
                ->push(['access_token' => 'token-1', 'token_type' => 'Bearer'])
                ->push(['access_token' => 'token-2', 'token_type' => 'Bearer']),
            $endpoint => static function (Request $request) {
                $dimensions = $request->data()['dimensions'] ?? [];

                if ($dimensions === ['date', 'query']) {
                    return Http::response([
                        'rows' => [[
                            'keys' => ['2026-11-20', 'laravel ecommerce'],
                            'clicks' => 12,
                            'impressions' => 240,
                            'ctr' => 0.05,
                            'position' => 4.25,
                        ]],
                    ]);
                }

                if ($dimensions === ['date', 'page']) {
                    return Http::response([
                        'rows' => [[
                            'keys' => ['2026-11-20', 'https://example.com/products/example'],
                            'clicks' => 8,
                            'impressions' => 160,
                            'ctr' => 0.05,
                            'position' => 5.5,
                        ]],
                    ]);
                }

                return Http::response([], 400);
            },
        ]);

        $connection = $this->activeConnection($property);
        $adapter = app(SearchConsoleProviderAdapter::class);

        $queryPage = $adapter->fetchPage(
            $connection,
            new SeoPerformanceFetchRequestData(
                scope: SeoPerformanceSyncScope::SearchPerformance,
                dateFrom: new DateTimeImmutable('2026-11-20'),
                dateTo: new DateTimeImmutable('2026-11-21'),
                rowLimit: 1000,
            ),
        );

        self::assertFalse($queryPage->complete);
        self::assertFalse($queryPage->truncated);
        self::assertSame('page:0:q0', $queryPage->nextPageToken);
        $queryRow = $queryPage->rows[0] ?? null;
        self::assertIsArray($queryRow);
        self::assertSame('query', $queryRow['dimension_type'] ?? null);
        self::assertSame('laravel ecommerce', $queryRow['dimension_value'] ?? null);

        $landingPage = $adapter->fetchPage(
            $connection,
            new SeoPerformanceFetchRequestData(
                scope: SeoPerformanceSyncScope::SearchPerformance,
                dateFrom: new DateTimeImmutable('2026-11-20'),
                dateTo: new DateTimeImmutable('2026-11-21'),
                rowLimit: 1000,
                pageToken: $queryPage->nextPageToken,
            ),
        );

        self::assertTrue($landingPage->complete);
        self::assertFalse($landingPage->truncated);
        self::assertNull($landingPage->nextPageToken);
        $landingRow = $landingPage->rows[0] ?? null;
        self::assertIsArray($landingRow);
        self::assertSame('landing_page', $landingRow['dimension_type'] ?? null);
        self::assertSame('https://example.com/products/example', $landingRow['dimension_value'] ?? null);

        Http::assertSent(static function (Request $request) use ($endpoint): bool {
            if ($request->url() !== $endpoint) {
                return false;
            }

            return $request->method() === 'POST'
                && ($request->data()['type'] ?? null) === 'web'
                && ($request->data()['dataState'] ?? null) === 'final'
                && ($request->data()['aggregationType'] ?? null) === 'auto'
                && ($request->data()['rowLimit'] ?? null) === 1000
                && ($request->data()['startRow'] ?? null) === 0;
        });
    }

    private function activeConnection(string $property): SeoPerformanceConnection
    {
        $manager = app(SeoPerformanceConnectionManager::class);
        $connection = $manager->register(
            provider: SeoPerformanceProvider::SearchConsole,
            displayName: 'Primary Search Console',
            propertyIdentifier: $property,
            propertyUrl: 'https://example.com/',
            timezone: 'UTC',
        );

        return $manager->recordSuccessfulVerification($connection);
    }

    private function configureCredentials(): void
    {
        $fixture = dirname(__DIR__, 3).'/Fixtures/Seo/Performance/search-console-test-private-key.pem';
        $privateKey = file_get_contents($fixture);

        if (! is_string($privateKey) || trim($privateKey) === '') {
            throw new RuntimeException('Unable to read the Search Console test key fixture.');
        }

        $file = tempnam(sys_get_temp_dir(), 'mwt-gsc-');
        if ($file === false) {
            throw new RuntimeException('Unable to create a Search Console credential fixture.');
        }

        file_put_contents($file, json_encode([
            'type' => 'service_account',
            'private_key_id' => 'test-key-id',
            'private_key' => $privateKey,
            'client_email' => 'seo-test@example-project.iam.gserviceaccount.com',
        ], JSON_THROW_ON_ERROR));

        $this->credentialFile = $file;
        config()->set('seo_performance.search_console.service_account_json', $file);
    }
}
