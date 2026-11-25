<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Models\Admin;
use App\Models\SeoAuditRun;
use DomainException;
use Illuminate\Support\Facades\Cache;

final readonly class SeoAuditAdminRunStarter
{
    private const LOCK_NAME = 'seo-audit:admin-run-start';

    private const LOCK_SECONDS = 15;

    public function __construct(
        private SeoAuditRunQueue $queue,
    ) {}

    public function start(Admin $admin): SeoAuditRun
    {
        $lock = Cache::lock(
            self::LOCK_NAME,
            self::LOCK_SECONDS,
        );

        if (! $lock->get()) {
            throw new DomainException(
                'Another SEO audit start request is already being processed.',
            );
        }

        try {
            $hasActiveRun =
                SeoAuditRun::query()
                    ->whereIn(
                        'status',
                        [
                            SeoAuditRunStatus::Requested->value,
                            SeoAuditRunStatus::Running->value,
                        ],
                    )
                    ->exists();

            if ($hasActiveRun) {
                throw new DomainException(
                    'An SEO audit is already queued or running.',
                );
            }

            return $this->queue->requestAndDispatch($admin);
        } finally {
            $lock->release();
        }
    }
}
