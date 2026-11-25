<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Performance;

use PHPUnit\Framework\TestCase;

final class GoogleAnalyticsQueuedSyncOrchestrationContractTest extends TestCase
{
    public function test_o7_is_bounded_retry_safe_and_reuses_generic_sync_progress(): void
    {
        $root = dirname(__DIR__, 4);

        $job = file_get_contents(
            $root.'/app/Jobs/Seo/Performance/ProcessGoogleAnalyticsSyncRun.php',
        );
        $orchestrator = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Services/GoogleAnalyticsSyncOrchestrator.php',
        );
        $action = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Actions/QueueGoogleAnalyticsSyncAction.php',
        );
        $progressMigration = file_get_contents(
            $root.'/database/migrations/2026_11_24_194000_add_search_console_progress_to_seo_performance_sync_runs_table.php',
        );

        self::assertIsString($job);
        self::assertIsString($orchestrator);
        self::assertIsString($action);
        self::assertIsString($progressMigration);

        self::assertStringContainsString('ShouldQueue', $job);
        self::assertStringContainsString('ShouldBeUniqueUntilProcessing', $job);
        self::assertStringContainsString('WithoutOverlapping', $job);
        self::assertStringContainsString('public int $tries = 3', $job);
        self::assertStringContainsString('return [10, 60, 300]', $job);
        self::assertStringContainsString('google_analytics_sync_job_failed', $job);
        self::assertStringNotContainsString('$exception->getMessage()', $job);

        self::assertStringContainsString('ROWS_PER_QUEUE_PASS = 1000', $orchestrator);
        self::assertStringContainsString('MAX_QUEUE_PASSES = 30', $orchestrator);
        self::assertStringContainsString('provider_page_token', $orchestrator);
        self::assertStringContainsString('provider_complete', $orchestrator);
        self::assertStringContainsString('provider_truncated', $orchestrator);
        self::assertStringContainsString('lockForUpdate()', $orchestrator);
        self::assertStringContainsString('GoogleAnalyticsLandingPageWriter', $orchestrator);
        self::assertStringContainsString('SeoPerformanceSyncLifecycle', $orchestrator);
        self::assertStringContainsString('LandingAnalytics', $orchestrator);

        self::assertStringContainsString('ProcessGoogleAnalyticsSyncRun::dispatch', $action);
        self::assertStringContainsString("'seo-performance'", $action);
        self::assertStringContainsString('provider_page_token', $progressMigration);
        self::assertStringContainsString('spsr_status_provider_done_idx', $progressMigration);

        self::assertStringNotContainsString('SearchConsoleProviderAdapter', $job.$orchestrator.$action);
        self::assertStringNotContainsString('Route::', $job.$orchestrator.$action);
        self::assertStringNotContainsString('schedule(', $job.$orchestrator.$action);
        self::assertStringNotContainsString('Controller', $job.$orchestrator.$action);
    }
}
