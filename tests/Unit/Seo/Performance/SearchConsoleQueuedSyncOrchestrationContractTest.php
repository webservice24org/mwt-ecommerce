<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Performance;

use PHPUnit\Framework\TestCase;

final class SearchConsoleQueuedSyncOrchestrationContractTest extends TestCase
{
    public function test_o4_is_bounded_retry_safe_and_keeps_sync_off_the_http_request_path(): void
    {
        $root = dirname(__DIR__, 4);

        $job = file_get_contents(
            $root.'/app/Jobs/Seo/Performance/ProcessSearchConsoleSyncRun.php',
        );
        $orchestrator = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Services/SearchConsoleSyncOrchestrator.php',
        );
        $action = file_get_contents(
            $root.'/app/Domain/Seo/Performance/Actions/QueueSearchConsoleSyncAction.php',
        );
        $migration = file_get_contents(
            $root.'/database/migrations/2026_11_24_194000_add_search_console_progress_to_seo_performance_sync_runs_table.php',
        );

        self::assertIsString($job);
        self::assertIsString($orchestrator);
        self::assertIsString($action);
        self::assertIsString($migration);

        self::assertStringContainsString('ShouldQueue', $job);
        self::assertStringContainsString('ShouldBeUniqueUntilProcessing', $job);
        self::assertStringContainsString('WithoutOverlapping', $job);
        self::assertStringContainsString('public int $tries = 3', $job);
        self::assertStringContainsString('return [10, 60, 300]', $job);
        self::assertStringContainsString('search_console_sync_job_failed', $job);
        self::assertStringNotContainsString('$exception->getMessage()', $job);

        self::assertStringContainsString('ROWS_PER_QUEUE_PASS = 1000', $orchestrator);
        self::assertStringContainsString('MAX_QUEUE_PASSES = 60', $orchestrator);
        self::assertStringContainsString('provider_complete', $orchestrator);
        self::assertStringContainsString('provider_truncated', $orchestrator);
        self::assertStringContainsString('lockForUpdate()', $orchestrator);
        self::assertStringContainsString('SearchConsolePerformancePageWriter', $orchestrator);
        self::assertStringContainsString('SeoPerformanceSyncLifecycle', $orchestrator);

        self::assertStringContainsString('ProcessSearchConsoleSyncRun::dispatch', $action);
        self::assertStringContainsString("'seo-performance'", $action);
        self::assertStringContainsString('spsr_status_provider_done_idx', $migration);

        self::assertStringNotContainsString('GoogleAnalytics', $job.$orchestrator.$action);
        self::assertStringNotContainsString('Route::', $job.$orchestrator.$action);
        self::assertStringNotContainsString('schedule(', $job.$orchestrator.$action);
        self::assertStringNotContainsString('Controller', $job.$orchestrator.$action);
    }
}
