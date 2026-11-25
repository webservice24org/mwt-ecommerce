<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Performance;

use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncMode;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Domain\Seo\Performance\Services\SeoPerformanceConnectionManager;
use App\Domain\Seo\Performance\Services\SeoPerformanceSyncLifecycle;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoPerformanceSyncLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_console_sync_has_a_bounded_retry_safe_lifecycle(): void
    {
        $manager = app(SeoPerformanceConnectionManager::class);
        $connection = $manager->register(
            SeoPerformanceProvider::SearchConsole,
            'Search Console',
            'sc-domain:example.com',
            'https://example.com',
            'UTC',
        );
        $connection = $manager->recordSuccessfulVerification($connection);

        $lifecycle = app(SeoPerformanceSyncLifecycle::class);
        $run = $lifecycle->request(
            $connection,
            SeoPerformanceSyncScope::SearchPerformance,
            SeoPerformanceSyncMode::Manual,
            CarbonImmutable::parse('2026-11-01'),
            CarbonImmutable::parse('2026-11-24'),
        );

        self::assertSame(SeoPerformanceSyncStatus::Queued, $run->status);
        self::assertSame(0, $run->attempt_count);

        $run = $lifecycle->start($run);
        self::assertSame(SeoPerformanceSyncStatus::Running, $run->status);
        self::assertSame(1, $run->attempt_count);

        $run = $lifecycle->start($run);
        self::assertSame(1, $run->attempt_count);

        $run = $lifecycle->complete($run, 120, 118);
        self::assertSame(SeoPerformanceSyncStatus::Completed, $run->status);
        self::assertSame(120, $run->rows_received);
        self::assertSame(118, $run->rows_persisted);

        $again = $lifecycle->complete($run, 120, 118);
        self::assertSame(SeoPerformanceSyncStatus::Completed, $again->status);
    }

    public function test_provider_capabilities_fail_closed(): void
    {
        $manager = app(SeoPerformanceConnectionManager::class);
        $connection = $manager->register(
            SeoPerformanceProvider::GoogleAnalytics,
            'GA4',
            'properties/123456',
            'https://example.com',
            'UTC',
        );
        $connection = $manager->recordSuccessfulVerification($connection);

        $this->expectException(DomainException::class);

        app(SeoPerformanceSyncLifecycle::class)->request(
            $connection,
            SeoPerformanceSyncScope::SearchPerformance,
            SeoPerformanceSyncMode::Manual,
            CarbonImmutable::parse('2026-11-01'),
            CarbonImmutable::parse('2026-11-24'),
        );
    }

    public function test_sync_windows_are_bounded_to_ninety_days(): void
    {
        $manager = app(SeoPerformanceConnectionManager::class);
        $connection = $manager->register(
            SeoPerformanceProvider::SearchConsole,
            'Search Console',
            'sc-domain:example.org',
            'https://example.org',
            'UTC',
        );
        $connection = $manager->recordSuccessfulVerification($connection);

        $this->expectException(DomainException::class);

        app(SeoPerformanceSyncLifecycle::class)->request(
            $connection,
            SeoPerformanceSyncScope::SearchPerformance,
            SeoPerformanceSyncMode::Backfill,
            CarbonImmutable::parse('2026-01-01'),
            CarbonImmutable::parse('2026-04-01'),
        );
    }
}
