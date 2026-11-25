<?php

declare(strict_types=1);

namespace App\Jobs\Seo\Audit;

use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Services\SeoAuditQueuedOrchestrator;
use App\Domain\Seo\Audit\Services\SeoAuditRunLifecycle;
use App\Models\SeoAuditRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class ProcessSeoAuditRun implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 900;

    /**
     * @param  list<string>  $seedReferences
     */
    public function __construct(
        public readonly int $runId,
        public readonly string $canonicalOrigin,
        public readonly array $seedReferences = ['/'],
    ) {}

    public function uniqueId(): string
    {
        return 'seo-audit-run:'.$this->runId;
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [
            10,
            60,
            300,
        ];
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(
                'seo-audit-run:'.$this->runId,
            ))
                ->releaseAfter(5)
                ->expireAfter($this->timeout + 60),
        ];
    }

    public function handle(
        SeoAuditQueuedOrchestrator $orchestrator,
    ): void {
        $run = SeoAuditRun::query()->find($this->runId);

        if ($run === null) {
            return;
        }

        if ($run->status === SeoAuditRunStatus::Failed) {
            return;
        }

        if (
            $run->status === SeoAuditRunStatus::Completed
            && $run->getAttribute('internal_links_built_at') !== null
        ) {
            return;
        }

        $step = $orchestrator->advance(
            run: $run,
            canonicalOrigin: $this->canonicalOrigin,
            seedReferences: $this->seedReferences,
        );

        if ($step->completed) {
            return;
        }

        self::dispatch(
            $this->runId,
            $this->canonicalOrigin,
            $this->seedReferences,
        )
            ->onQueue($this->queueName())
            ->delay(
                now()->addSeconds(
                    max(
                        1,
                        (int) config(
                            'seo-audit.continuation_delay_seconds',
                            2,
                        ),
                    ),
                ),
            );
    }

    public function failed(Throwable $exception): void
    {
        $run = SeoAuditRun::query()->find($this->runId);

        if (
            $run === null
            || $run->status === SeoAuditRunStatus::Completed
            || $run->status === SeoAuditRunStatus::Failed
        ) {
            return;
        }

        try {
            app(SeoAuditRunLifecycle::class)->fail(
                run: $run,
                code: 'audit_job_failed',
                message: 'SEO audit processing failed after queue retries.',
            );
        } catch (Throwable) {
            // Preserve the original queue failure as the primary failure.
        }
    }

    private function queueName(): string
    {
        $queue = trim((string) config(
            'seo-audit.queue',
            'seo-audit',
        ));

        return $queue === ''
            ? 'seo-audit'
            : $queue;
    }
}
