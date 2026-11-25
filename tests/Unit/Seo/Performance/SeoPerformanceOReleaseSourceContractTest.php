<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Performance;

use PHPUnit\Framework\TestCase;

final class SeoPerformanceOReleaseSourceContractTest extends TestCase
{
    public function test_o_release_keeps_credentials_private_queues_bounded_and_cross_source_reporting_non_causal(): void
    {
        $root = dirname(__DIR__, 4);

        $searchResolver = $this->source(
            $root.'/app/Domain/Seo/Performance/Services/SearchConsoleServiceAccountCredentialResolver.php',
        );
        $analyticsResolver = $this->source(
            $root.'/app/Domain/Seo/Performance/Services/GoogleAnalyticsServiceAccountCredentialResolver.php',
        );
        $searchJob = $this->source(
            $root.'/app/Jobs/Seo/Performance/ProcessSearchConsoleSyncRun.php',
        );
        $analyticsJob = $this->source(
            $root.'/app/Jobs/Seo/Performance/ProcessGoogleAnalyticsSyncRun.php',
        );
        $dashboard = $this->source(
            $root.'/app/Domain/Seo/Performance/Queries/GetUnifiedSeoPerformanceDashboardQuery.php',
        );
        $connectionsMigration = $this->source(
            $root.'/database/migrations/2026_11_24_190000_create_seo_performance_connections_table.php',
        );
        $syncRunsMigration = $this->source(
            $root.'/database/migrations/2026_11_24_190100_create_seo_performance_sync_runs_table.php',
        );

        foreach ([$searchResolver, $analyticsResolver] as $resolver) {
            self::assertStringContainsString('MAX_CREDENTIAL_FILE_BYTES = 131_072', $resolver);
            self::assertStringContainsString('rejectPublicPath', $resolver);
            self::assertStringContainsString("'credentials_insecure'", $resolver);
        }

        foreach ([$connectionsMigration, $syncRunsMigration] as $migration) {
            $lower = strtolower($migration);
            self::assertStringNotContainsString('access_token', $lower);
            self::assertStringNotContainsString('refresh_token', $lower);
            self::assertStringNotContainsString('private_key', $lower);
            self::assertStringNotContainsString('client_secret', $lower);
        }

        foreach ([$searchJob, $analyticsJob] as $job) {
            self::assertStringContainsString('ShouldBeUniqueUntilProcessing', $job);
            self::assertStringContainsString('WithoutOverlapping', $job);
            self::assertStringContainsString('public int $tries = 3;', $job);
            self::assertStringContainsString('return [10, 60, 300];', $job);
            self::assertStringContainsString('public int $timeout = 90;', $job);
        }

        self::assertStringContainsString("'search_console_sync_job_failed'", $searchJob);
        self::assertStringContainsString("'google_analytics_sync_job_failed'", $analyticsJob);
        self::assertStringNotContainsString('$exception->getMessage()', $searchJob);
        self::assertStringNotContainsString('$exception->getMessage()', $analyticsJob);

        self::assertStringContainsString('PAGE_CANDIDATES_PER_SOURCE = 200', $dashboard);
        self::assertStringContainsString('PAGES_PER_PAGE = 20', $dashboard);
        self::assertStringContainsString('INSIGHT_LIMIT = 12', $dashboard);
        self::assertStringContainsString('whereDate(', $dashboard);
        self::assertStringContainsString('does not attribute GA4 sessions', $dashboard);
        self::assertStringContainsString('not as attribution of GA4 sessions', $dashboard);
        self::assertStringNotContainsString('search_conversion_rate', $dashboard);
        self::assertStringNotContainsString('ga4_sessions_from_search_console', $dashboard);
    }

    private function source(string $path): string
    {
        $source = file_get_contents($path);
        self::assertIsString($source, $path);

        return $source;
    }
}
