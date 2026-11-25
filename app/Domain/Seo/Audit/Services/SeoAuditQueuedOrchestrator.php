<?php

declare(strict_types=1);

namespace App\Domain\Seo\Audit\Services;

use App\Domain\Seo\Audit\Data\SeoAuditOrchestrationStepData;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Queries\SeoAuditCrawlInventorySummaryQuery;
use App\Models\SeoAuditRun;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class SeoAuditQueuedOrchestrator
{
    public const VERSION = 'seo-audit-orchestration-v1';

    public const CRAWL_FETCHES_PER_QUEUE_PASS = 50;

    public const MAX_QUEUE_PASSES = 125;

    public const IMAGE_HEALTH_CHECKS_PER_QUEUE_PASS = 50;

    public function __construct(
        private SeoAuditRunLifecycle $lifecycle,
        private SeoAuditCatalogCoverageSeeder $catalog,
        private SeoAuditBoundedCrawler $crawler,
        private SeoAuditImageHealthEnricher $imageHealth,
        private SeoAuditTechnicalRuleEvaluator $rules,
        private SeoAuditInternalLinkOpportunityBuilder $internalLinks,
        private SeoAuditCrawlInventorySummaryQuery $inventorySummary,
    ) {}

    /**
     * @param  list<string>  $seedReferences
     */
    public function advance(
        SeoAuditRun $run,
        string $canonicalOrigin,
        array $seedReferences = ['/'],
    ): SeoAuditOrchestrationStepData {
        $current = SeoAuditRun::query()->findOrFail($run->getKey());

        if ($current->status === SeoAuditRunStatus::Completed) {
            $current = $this->ensureInternalLinksBuilt($current);

            return $this->completedStep($current);
        }

        if ($current->status === SeoAuditRunStatus::Failed) {
            throw new DomainException(
                'Failed SEO audit runs cannot be resumed by the queue orchestrator.',
            );
        }

        if ($current->status === SeoAuditRunStatus::Requested) {
            $current = $this->lifecycle->start($current);
        }

        $current = $this->beginQueuePass($current);

        if ($current->catalog_seeded_at === null) {
            $this->catalog->seed(
                run: $current,
                canonicalHost: $canonicalOrigin,
            );

            $current = $this->markStage(
                run: $current,
                column: 'catalog_seeded_at',
            );
        }

        if ($current->crawl_settled_at === null) {
            $crawl = $this->crawler->execute(
                run: $current,
                canonicalHost: $canonicalOrigin,
                seedReferences: $seedReferences,
                maxFetches: self::CRAWL_FETCHES_PER_QUEUE_PASS,
            );

            if ($crawl->discoveredCount > 0) {
                return new SeoAuditOrchestrationStepData(
                    completed: false,
                    queuePass: $current->queue_passes,
                    pagesTotal: $crawl->pagesTotal,
                    fetchedCount: $crawl->fetchedCount,
                    failedCount: $crawl->failedCount,
                    discoveredCount: $crawl->discoveredCount,
                    urlsScanned: $crawl->fetchedCount + $crawl->failedCount,
                );
            }

            $current = $this->markStage(
                run: $current,
                column: 'crawl_settled_at',
            );
        }

        $inventory = $this->inventorySummary->get($current);

        if ($inventory->discoveredCount !== 0) {
            throw new DomainException(
                'SEO audit crawl inventory changed after it was marked settled.',
            );
        }

        if ($current->image_health_enriched_at === null) {
            $imageHealth = $this->imageHealth->enrichChunk(
                run: $current,
                canonicalHost: $canonicalOrigin,
                maxResources: self::IMAGE_HEALTH_CHECKS_PER_QUEUE_PASS,
            );

            if ($imageHealth->remainingResources > 0) {
                return new SeoAuditOrchestrationStepData(
                    completed: false,
                    queuePass: $current->queue_passes,
                    pagesTotal: $inventory->pagesTotal,
                    fetchedCount: $inventory->fetchedCount,
                    failedCount: $inventory->failedCount,
                    discoveredCount: 0,
                    urlsScanned: $inventory->scannedCount,
                );
            }

            $current = $this->markStage(
                run: $current,
                column: 'image_health_enriched_at',
            );
        }

        if ($current->evaluated_at === null) {
            $this->rules->evaluate($current);

            $current = $this->markStage(
                run: $current,
                column: 'evaluated_at',
            );
        }

        $completed = $this->lifecycle->complete(
            run: $current,
            urlsScanned: $inventory->scannedCount,
        );

        $completed = $this->ensureInternalLinksBuilt($completed);

        return new SeoAuditOrchestrationStepData(
            completed: true,
            queuePass: $completed->queue_passes,
            pagesTotal: $inventory->pagesTotal,
            fetchedCount: $inventory->fetchedCount,
            failedCount: $inventory->failedCount,
            discoveredCount: 0,
            urlsScanned: $inventory->scannedCount,
        );
    }

    private function beginQueuePass(SeoAuditRun $run): SeoAuditRun
    {
        return DB::transaction(function () use ($run): SeoAuditRun {
            $current = SeoAuditRun::query()
                ->lockForUpdate()
                ->findOrFail($run->getKey());

            if ($current->status !== SeoAuditRunStatus::Running) {
                throw new DomainException(
                    'SEO audit queue orchestration requires a running audit run.',
                );
            }

            if (
                $current->orchestration_version !== null
                && $current->orchestration_version !== self::VERSION
            ) {
                throw new DomainException(
                    'SEO audit orchestration version changed during an active run.',
                );
            }

            $nextPass = $current->queue_passes + 1;

            if ($nextPass > self::MAX_QUEUE_PASSES) {
                throw new DomainException(
                    'SEO audit queue pass limit reached before completion.',
                );
            }

            $current->forceFill([
                'orchestration_version' => self::VERSION,
                'queue_passes' => $nextPass,
                'last_progress_at' => now(),
            ])->save();

            return $current;
        });
    }

    private function markStage(
        SeoAuditRun $run,
        string $column,
    ): SeoAuditRun {
        $allowed = [
            'catalog_seeded_at',
            'crawl_settled_at',
            'image_health_enriched_at',
            'evaluated_at',
        ];

        if (! in_array($column, $allowed, true)) {
            throw new DomainException(
                'Unsupported SEO audit orchestration stage marker.',
            );
        }

        return DB::transaction(function () use ($run, $column): SeoAuditRun {
            $current = SeoAuditRun::query()
                ->lockForUpdate()
                ->findOrFail($run->getKey());

            if ($current->status !== SeoAuditRunStatus::Running) {
                throw new DomainException(
                    'SEO audit orchestration stage changed after the run stopped.',
                );
            }

            if ($current->getAttribute($column) === null) {
                $current->forceFill([
                    $column => now(),
                    'last_progress_at' => now(),
                ])->save();
            }

            return $current;
        });
    }

    private function ensureInternalLinksBuilt(SeoAuditRun $run): SeoAuditRun
    {
        $run->refresh();

        if ($run->status !== SeoAuditRunStatus::Completed) {
            throw new DomainException(
                'Internal linking finalization requires a completed SEO audit run.',
            );
        }

        if ($run->getAttribute('internal_links_built_at') !== null) {
            return $run;
        }

        $this->internalLinks->rebuild($run);

        return DB::transaction(function () use ($run): SeoAuditRun {
            $current = SeoAuditRun::query()
                ->lockForUpdate()
                ->findOrFail($run->getKey());

            if ($current->status !== SeoAuditRunStatus::Completed) {
                throw new DomainException(
                    'SEO audit status changed during internal linking finalization.',
                );
            }

            if ($current->getAttribute('internal_links_built_at') === null) {
                $current->forceFill([
                    'internal_links_built_at' => now(),
                    'last_progress_at' => now(),
                ])->save();
            }

            return $current;
        });
    }

    private function completedStep(SeoAuditRun $run): SeoAuditOrchestrationStepData
    {
        $inventory = $this->inventorySummary->get($run);

        return new SeoAuditOrchestrationStepData(
            completed: true,
            queuePass: $run->queue_passes,
            pagesTotal: $inventory->pagesTotal,
            fetchedCount: $inventory->fetchedCount,
            failedCount: $inventory->failedCount,
            discoveredCount: $inventory->discoveredCount,
            urlsScanned: $run->urls_scanned,
        );
    }
}
