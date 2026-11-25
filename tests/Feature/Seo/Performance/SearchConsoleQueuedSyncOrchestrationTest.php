<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Performance;

use App\Domain\Seo\Performance\Actions\QueueSearchConsoleSyncAction;
use App\Domain\Seo\Performance\Enums\SeoPerformanceProvider;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncMode;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncScope;
use App\Domain\Seo\Performance\Enums\SeoPerformanceSyncStatus;
use App\Domain\Seo\Performance\Services\SearchConsoleApiClient;
use App\Domain\Seo\Performance\Services\SearchConsoleServiceAccountAccessTokenProvider;
use App\Domain\Seo\Performance\Services\SearchConsoleSyncOrchestrator;
use App\Domain\Seo\Performance\Services\SeoPerformanceConnectionManager;
use App\Domain\Seo\Performance\Services\SeoPerformanceSyncLifecycle;
use App\Jobs\Seo\Performance\ProcessSearchConsoleSyncRun;
use App\Models\SeoPerformanceSyncRun;
use App\Models\SeoSearchConsolePerformanceRow;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

final class SearchConsoleQueuedSyncOrchestrationTest extends TestCase
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

    public function test_orchestrator_advances_query_then_landing_page_and_completes_run(): void
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

        $run = $this->queuedRun($property);
        $orchestrator = app(SearchConsoleSyncOrchestrator::class);

        $first = $orchestrator->advance($run);
        self::assertFalse($first->completed);
        self::assertSame(1, $first->queuePass);
        self::assertSame(1, $first->rowsReceived);
        self::assertSame(1, $first->rowsPersisted);

        $run->refresh();
        self::assertSame(SeoPerformanceSyncStatus::Running, $run->status);
        self::assertSame('page:0:q0', $run->provider_page_token);
        self::assertFalse($run->provider_complete);

        $second = $orchestrator->advance($run);
        self::assertTrue($second->completed);
        self::assertSame(2, $second->queuePass);
        self::assertSame(2, $second->rowsReceived);
        self::assertSame(2, $second->rowsPersisted);
        self::assertFalse($second->truncated);

        $run->refresh();
        self::assertSame(SeoPerformanceSyncStatus::Completed, $run->status);
        self::assertTrue($run->provider_complete);
        self::assertSame(2, $run->queue_pass_count);
        self::assertSame(2, SeoSearchConsolePerformanceRow::query()->count());
    }

    public function test_provider_complete_running_run_can_finish_without_refetching(): void
    {
        Http::preventStrayRequests();

        $run = $this->queuedRun('sc-domain:example.com');
        $run = app(SeoPerformanceSyncLifecycle::class)->start($run);
        $run->forceFill([
            'provider_complete' => true,
            'provider_truncated' => true,
            'rows_received' => 10,
            'rows_persisted' => 10,
        ])->save();

        $step = app(SearchConsoleSyncOrchestrator::class)->advance($run);

        self::assertTrue($step->completed);
        self::assertTrue($step->truncated);
        self::assertSame(10, $step->rowsReceived);
        self::assertSame(10, $step->rowsPersisted);
        self::assertSame(SeoPerformanceSyncStatus::Completed, $run->fresh()->status);
    }

    public function test_queue_action_dispatches_only_the_search_console_sync_job(): void
    {
        Queue::fake();

        $run = $this->queuedRun('sc-domain:example.com');
        app(QueueSearchConsoleSyncAction::class)->execute($run);

        Queue::assertPushed(
            ProcessSearchConsoleSyncRun::class,
            static fn (ProcessSearchConsoleSyncRun $job): bool => $job->runId === $run->getKey(),
        );
    }

    private function queuedRun(string $property): SeoPerformanceSyncRun
    {
        $manager = app(SeoPerformanceConnectionManager::class);
        $connection = $manager->register(
            provider: SeoPerformanceProvider::SearchConsole,
            displayName: 'Primary Search Console',
            propertyIdentifier: $property,
            propertyUrl: 'https://example.com/',
            timezone: 'UTC',
        );
        $connection = $manager->recordSuccessfulVerification($connection);

        return app(SeoPerformanceSyncLifecycle::class)->request(
            connection: $connection,
            scope: SeoPerformanceSyncScope::SearchPerformance,
            mode: SeoPerformanceSyncMode::Manual,
            dateFrom: new DateTimeImmutable('2026-11-20'),
            dateTo: new DateTimeImmutable('2026-11-21'),
        );
    }

    private function configureCredentials(): void
    {
        $fixture = dirname(__DIR__, 3).'/Fixtures/Seo/Performance/search-console-test-private-key.pem';
        $privateKey = file_get_contents($fixture);

        if (! is_string($privateKey) || trim($privateKey) === '') {
            throw new RuntimeException('Unable to read the Search Console test key fixture.');
        }

        $file = tempnam(sys_get_temp_dir(), 'mwt-gsc-o4-');
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
