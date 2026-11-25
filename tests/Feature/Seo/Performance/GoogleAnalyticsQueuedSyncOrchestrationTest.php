<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Performance;

use App\Domain\Seo\Performance\Actions\QueueGoogleAnalyticsSyncAction;
use App\Domain\Seo\Performance\Enums\SeoPerformanceConnectionStatus;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncMode;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Domain\Seo\Performance\Services\GoogleAnalyticsDataApiClient;
use App\Domain\Seo\Performance\Services\GoogleAnalyticsServiceAccountAccessTokenProvider;
use App\Domain\Seo\Performance\Services\GoogleAnalyticsSyncOrchestrator;
use App\Domain\Seo\Performance\Services\SeoPerformanceConnectionManager;
use App\Domain\Seo\Performance\Services\SeoPerformanceSyncLifecycle;
use App\Jobs\Seo\Performance\ProcessGoogleAnalyticsSyncRun;
use App\Models\SeoGoogleAnalyticsLandingPageRow;
use App\Models\SeoPerformanceSyncRun;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class GoogleAnalyticsQueuedSyncOrchestrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_orchestrator_advances_bounded_pages_and_completes_run(): void
    {
        $this->configureCredentials();
        $endpoint = GoogleAnalyticsDataApiClient::RUN_REPORT_BASE_URI.'/123456789:runReport';
        $firstRows = [];

        for ($index = 0; $index < 1000; $index++) {
            $firstRows[] = $this->apiRow(
                '20261120',
                '/products/item-'.$index,
                1,
            );
        }

        Http::fake([
            GoogleAnalyticsServiceAccountAccessTokenProvider::TOKEN_URI => Http::sequence()
                ->push(['access_token' => 'token-1', 'token_type' => 'Bearer'])
                ->push(['access_token' => 'token-2', 'token_type' => 'Bearer']),
            $endpoint => Http::sequence()
                ->push($this->apiResponse($firstRows, 1001))
                ->push($this->apiResponse([
                    $this->apiRow('20261121', '/products/final-item', 2),
                ], 1001)),
        ]);

        $run = $this->queuedRun();
        $orchestrator = app(GoogleAnalyticsSyncOrchestrator::class);

        $first = $orchestrator->advance($run);
        self::assertFalse($first->completed);
        self::assertSame(1, $first->queuePass);
        self::assertSame(1000, $first->rowsReceived);
        self::assertSame(1000, $first->rowsPersisted);

        $run->refresh();
        self::assertSame(SeoPerformanceSyncStatus::Running, $run->status);
        self::assertSame('offset:1000', $run->provider_page_token);
        self::assertFalse($run->provider_complete);

        $second = $orchestrator->advance($run);
        self::assertTrue($second->completed);
        self::assertSame(2, $second->queuePass);
        self::assertSame(1001, $second->rowsReceived);
        self::assertSame(1001, $second->rowsPersisted);
        self::assertFalse($second->truncated);

        $run->refresh();
        self::assertSame(SeoPerformanceSyncStatus::Completed, $run->status);
        self::assertTrue($run->provider_complete);
        self::assertSame('google-analytics-sync-v1', $run->orchestration_version);
        self::assertSame(2, $run->queue_pass_count);
        self::assertSame(1001, SeoGoogleAnalyticsLandingPageRow::query()->count());

        Http::assertSent(static function (Request $request): bool {
            if ($request->url() !== GoogleAnalyticsDataApiClient::RUN_REPORT_BASE_URI.'/123456789:runReport') {
                return false;
            }

            return $request['limit'] === 1000
                && in_array($request['offset'], [0, 1000], true);
        });
    }

    public function test_provider_complete_running_run_can_finish_without_refetching(): void
    {
        Http::preventStrayRequests();

        $run = $this->queuedRun();
        $run = app(SeoPerformanceSyncLifecycle::class)->start($run);
        $run->forceFill([
            'provider_complete' => true,
            'provider_truncated' => true,
            'rows_received' => 25_000,
            'rows_persisted' => 25_000,
        ])->save();

        $step = app(GoogleAnalyticsSyncOrchestrator::class)->advance($run);

        self::assertTrue($step->completed);
        self::assertTrue($step->truncated);
        self::assertSame(25_000, $step->rowsReceived);
        self::assertSame(25_000, $step->rowsPersisted);
        self::assertSame(SeoPerformanceSyncStatus::Completed, $run->fresh()->status);
    }

    public function test_queue_action_dispatches_only_the_google_analytics_sync_job(): void
    {
        Queue::fake();

        $run = $this->queuedRun();
        app(QueueGoogleAnalyticsSyncAction::class)->execute($run);

        Queue::assertPushed(
            ProcessGoogleAnalyticsSyncRun::class,
            static fn (ProcessGoogleAnalyticsSyncRun $job): bool => $job->runId === $run->getKey(),
        );
    }

    private function queuedRun(): SeoPerformanceSyncRun
    {
        $manager = app(SeoPerformanceConnectionManager::class);
        $connection = $manager->register(
            provider: SeoPerformanceProvider::GoogleAnalytics,
            displayName: 'Primary GA4',
            propertyIdentifier: '123456789',
            propertyUrl: null,
            timezone: 'UTC',
        );
        $connection->forceFill([
            'status' => SeoPerformanceConnectionStatus::Active,
            'verified_at' => now(),
            'capabilities' => ['analytics_landing_pages'],
        ])->save();

        return app(SeoPerformanceSyncLifecycle::class)->request(
            connection: $connection->refresh(),
            scope: SeoPerformanceSyncScope::LandingAnalytics,
            mode: SeoPerformanceSyncMode::Manual,
            dateFrom: new DateTimeImmutable('2026-11-20'),
            dateTo: new DateTimeImmutable('2026-11-21'),
        );
    }

    private function configureCredentials(): void
    {
        $privateKey = file_get_contents(
            base_path('tests/Fixtures/Seo/Performance/google-analytics-test-private-key.pem'),
        );
        self::assertIsString($privateKey);

        $path = storage_path('framework/testing/google-analytics-o7-service-account.json');
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

        config()->set('seo_performance_google_analytics.service_account_json', $path);
    }

    /** @return array<string, mixed> */
    private function apiRow(string $date, string $landingPage, int $sessions): array
    {
        return [
            'dimensionValues' => [
                ['value' => $date],
                ['value' => $landingPage],
            ],
            'metricValues' => [
                ['value' => (string) $sessions],
                ['value' => (string) $sessions],
                ['value' => (string) $sessions],
                ['value' => '0'],
                ['value' => '0'],
                ['value' => '0'],
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private function apiResponse(array $rows, int $rowCount): array
    {
        return [
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
            'rows' => $rows,
            'rowCount' => $rowCount,
        ];
    }
}
