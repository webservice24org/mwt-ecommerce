<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Audit;

use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Services\SeoAuditQueuedOrchestrator;
use App\Domain\Seo\Audit\Services\SeoAuditRunLifecycle;
use App\Domain\Seo\Audit\Services\SeoAuditRunQueue;
use App\Jobs\Seo\Audit\ProcessSeoAuditRun;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class SeoAuditQueuedOrchestrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://example.test',
            'seo-audit.queue' => 'seo-audit',
            'seo-audit.continuation_delay_seconds' => 1,
        ]);
    }

    public function test_request_dispatches_unique_audit_job_on_configured_queue(): void
    {
        Queue::fake();

        $run = app(SeoAuditRunQueue::class)->requestAndDispatch(
            Admin::factory()->create(),
        );

        self::assertSame(
            SeoAuditRunStatus::Requested,
            $run->status,
        );

        Queue::assertPushedOn(
            'seo-audit',
            ProcessSeoAuditRun::class,
        );

        Queue::assertPushed(
            ProcessSeoAuditRun::class,
            static fn (ProcessSeoAuditRun $job): bool => $job->runId === $run->id
                && $job->canonicalOrigin === 'https://example.test',
        );
    }

    public function test_orchestrator_completes_a_settled_audit_and_persists_stage_markers(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://example.test/' => Http::response(
                '<html><head><title>Home</title><meta name="description" content="Store home"><link rel="canonical" href="/"></head><body><h1>Home</h1><p>Useful store content.</p></body></html>',
                200,
                ['Content-Type' => 'text/html'],
            ),
        ]);

        $run = app(SeoAuditRunLifecycle::class)->request(
            Admin::factory()->create(),
        );

        $step = app(SeoAuditQueuedOrchestrator::class)->advance(
            run: $run,
            canonicalOrigin: 'https://example.test',
        );

        $run->refresh();

        self::assertTrue($step->completed);
        self::assertSame(SeoAuditRunStatus::Completed, $run->status);
        self::assertSame(1, $run->urls_scanned);
        self::assertSame(1, $run->queue_passes);
        self::assertNotNull($run->catalog_seeded_at);
        self::assertNotNull($run->crawl_settled_at);
        self::assertNotNull($run->image_health_enriched_at);
        self::assertNotNull($run->evaluated_at);
        self::assertSame(
            SeoAuditQueuedOrchestrator::VERSION,
            $run->orchestration_version,
        );
    }

    public function test_large_discovery_is_split_across_queue_passes_before_completion(): void
    {
        Http::preventStrayRequests();

        Http::fake(static function (Request $request) {
            if ($request->url() === 'https://example.test/') {
                $links = '';

                for ($i = 1; $i <= 51; $i++) {
                    $links .= '<a href="/page-'.$i.'">Page '.$i.'</a>';
                }

                return Http::response(
                    '<html><head><title>Home</title><meta name="description" content="Home"><link rel="canonical" href="/"></head><body><h1>Home</h1>'.$links.'</body></html>',
                    200,
                    ['Content-Type' => 'text/html'],
                );
            }

            return Http::response(
                '<html><head><title>Child</title><meta name="description" content="Child"><link rel="canonical" href="'.$request->url().'"></head><body><h1>Child</h1><p>Child page content.</p></body></html>',
                200,
                ['Content-Type' => 'text/html'],
            );
        });

        $run = app(SeoAuditRunLifecycle::class)->request(
            Admin::factory()->create(),
        );

        $first = app(SeoAuditQueuedOrchestrator::class)->advance(
            run: $run,
            canonicalOrigin: 'https://example.test',
        );

        self::assertFalse($first->completed);
        self::assertGreaterThan(0, $first->discoveredCount);
        self::assertSame(1, $run->refresh()->queue_passes);
        self::assertSame(SeoAuditRunStatus::Running, $run->status);

        $second = app(SeoAuditQueuedOrchestrator::class)->advance(
            run: $run,
            canonicalOrigin: 'https://example.test',
        );

        self::assertTrue($second->completed);
        self::assertSame(52, $second->urlsScanned);
        self::assertSame(2, $run->refresh()->queue_passes);
        self::assertSame(SeoAuditRunStatus::Completed, $run->status);
    }

    public function test_exhausted_job_marks_non_terminal_run_failed_with_sanitized_message(): void
    {
        $run = app(SeoAuditRunLifecycle::class)->request(
            Admin::factory()->create(),
        );

        $job = new ProcessSeoAuditRun(
            runId: $run->id,
            canonicalOrigin: 'https://example.test',
        );

        $job->failed(
            new \RuntimeException('secret transport detail'),
        );

        $run->refresh();

        self::assertSame(SeoAuditRunStatus::Failed, $run->status);
        self::assertSame('audit_job_failed', $run->failure_code);
        self::assertSame(
            'SEO audit processing failed after queue retries.',
            $run->failure_message,
        );
        self::assertStringNotContainsString(
            'secret transport detail',
            (string) $run->failure_message,
        );
    }
}
