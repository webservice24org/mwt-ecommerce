<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Queries;

use App\Domain\Seo\Audit\Data\SeoAuditCrawlInventorySummaryData;
use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditInternalLink;
use App\Models\SeoAuditRun;

final class SeoAuditCrawlInventorySummaryQuery
{
    public function get(
        SeoAuditRun $run,
    ): SeoAuditCrawlInventorySummaryData {
        $counts =
            SeoAuditCrawlPage::query()
                ->where(
                    'seo_audit_run_id',
                    $run->getKey(),
                )
                ->selectRaw(
                    'state, COUNT(*) as aggregate',
                )
                ->groupBy('state')
                ->pluck(
                    'aggregate',
                    'state',
                );

        $discoveredCount =
            (int) (
                $counts[
                    SeoAuditCrawlState::Discovered
                        ->value
                ]
                ?? 0
            );

        $fetchedCount =
            (int) (
                $counts[
                    SeoAuditCrawlState::Fetched
                        ->value
                ]
                ?? 0
            );

        $failedCount =
            (int) (
                $counts[
                    SeoAuditCrawlState::Failed
                        ->value
                ]
                ?? 0
            );

        return new SeoAuditCrawlInventorySummaryData(
            pagesTotal: $discoveredCount
                + $fetchedCount
                + $failedCount,
            discoveredCount: $discoveredCount,
            fetchedCount: $fetchedCount,
            failedCount: $failedCount,
            scannedCount: $fetchedCount
                + $failedCount,
            linksTotal: SeoAuditInternalLink::query()
                ->where(
                    'seo_audit_run_id',
                    $run->getKey(),
                )
                ->count(),
        );
    }
}
