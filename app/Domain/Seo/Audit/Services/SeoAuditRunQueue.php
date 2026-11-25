<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Jobs\Seo\Audit\ProcessSeoAuditRun;
use App\Models\Admin;
use App\Models\SeoAuditRun;
use DomainException;
use Throwable;

final readonly class SeoAuditRunQueue
{
    public function __construct(
        private SeoAuditRunLifecycle $lifecycle,
        private SeoAuditCanonicalOriginResolver $origins,
    ) {}

    /**
     * @param  list<string>  $seedReferences
     */
    public function requestAndDispatch(
        Admin $admin,
        array $seedReferences = ['/'],
    ): SeoAuditRun {
        $run = $this->lifecycle->request($admin);

        try {
            $this->dispatch(
                run: $run,
                canonicalOrigin: $this->origins->resolve(),
                seedReferences: $seedReferences,
            );
        } catch (Throwable $exception) {
            $this->lifecycle->fail(
                run: $run,
                code: 'queue_dispatch_failed',
                message: 'SEO audit could not be dispatched to the queue.',
            );

            throw $exception;
        }

        return $run;
    }

    /**
     * @param  list<string>  $seedReferences
     */
    public function dispatch(
        SeoAuditRun $run,
        string $canonicalOrigin,
        array $seedReferences = ['/'],
    ): void {
        $run->refresh();

        if ($run->status !== SeoAuditRunStatus::Requested) {
            throw new DomainException(
                'Only requested SEO audit runs may be initially dispatched.',
            );
        }

        ProcessSeoAuditRun::dispatch(
            $run->id,
            $canonicalOrigin,
            $seedReferences,
        )->onQueue($this->queueName());
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
