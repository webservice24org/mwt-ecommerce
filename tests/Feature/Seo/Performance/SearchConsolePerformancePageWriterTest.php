<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Performance;

use App\Domain\Seo\Performance\Data\SeoPerformanceFetchPageData;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncMode;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Services\SearchConsolePerformancePageWriter;
use App\Domain\Seo\Performance\Services\SeoPerformanceConnectionManager;
use App\Domain\Seo\Performance\Services\SeoPerformanceSyncLifecycle;
use App\Models\SeoSearchConsolePerformanceRow;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SearchConsolePerformancePageWriterTest extends TestCase
{
    use RefreshDatabase;

    public function test_running_search_console_sync_persists_page_rows_idempotently(): void
    {
        $connectionManager = app(SeoPerformanceConnectionManager::class);
        $connection = $connectionManager->register(
            provider: SeoPerformanceProvider::SearchConsole,
            displayName: 'Primary Search Console',
            propertyIdentifier: 'sc-domain:example.com',
            propertyUrl: 'https://example.com/',
            timezone: 'UTC',
        );
        $connection = $connectionManager->recordSuccessfulVerification($connection);

        $lifecycle = app(SeoPerformanceSyncLifecycle::class);
        $run = $lifecycle->request(
            connection: $connection,
            scope: SeoPerformanceSyncScope::SearchPerformance,
            mode: SeoPerformanceSyncMode::Manual,
            dateFrom: new DateTimeImmutable('2026-11-20'),
            dateTo: new DateTimeImmutable('2026-11-21'),
        );
        $run = $lifecycle->start($run);

        $page = new SeoPerformanceFetchPageData(
            rows: [
                [
                    'dimension_type' => 'query',
                    'search_type' => 'web',
                    'date' => '2026-11-20',
                    'dimension_value' => 'laravel ecommerce',
                    'clicks' => 12.0,
                    'impressions' => 240.0,
                    'ctr' => 0.05,
                    'position' => 4.25,
                ],
                [
                    'dimension_type' => 'landing_page',
                    'search_type' => 'web',
                    'date' => '2026-11-20',
                    'dimension_value' => 'https://example.com/products/example',
                    'clicks' => 8.0,
                    'impressions' => 160.0,
                    'ctr' => 0.05,
                    'position' => 5.5,
                ],
            ],
            nextPageToken: null,
            complete: true,
        );

        $writer = app(SearchConsolePerformancePageWriter::class);

        self::assertSame(2, $writer->persist($run, $page));
        self::assertSame(2, $writer->persist($run, $page));
        self::assertSame(2, SeoSearchConsolePerformanceRow::query()->count());

        $query = SeoSearchConsolePerformanceRow::query()
            ->where('dimension_type', 'query')
            ->firstOrFail();

        self::assertSame('12.0000', $query->clicks);
        self::assertSame('240.0000', $query->impressions);
        self::assertSame('0.0500000000', $query->ctr);
        self::assertSame('4.2500', $query->position);
        self::assertSame(hash('sha256', 'laravel ecommerce'), $query->dimension_hash);
    }
}
