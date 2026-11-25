<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Audit;

use App\Domain\Seo\Audit\Data\SeoAuditFindingData;
use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditInternalLinkReviewState;
use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Enums\SeoAuditSeverity;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Domain\Seo\Audit\Services\SeoAuditInternalLinkReviewService;
use App\Domain\Seo\Audit\Services\SeoAuditQueuedOrchestrator;
use App\Domain\Seo\Audit\Services\SeoAuditRunLifecycle;
use App\Models\Admin;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditInternalLink;
use App\Models\SeoAuditInternalLinkOpportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoAuditNReleaseJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_queue_pipeline_builds_reviewable_internal_link_opportunities_once(): void
    {
        $admin = Admin::factory()->create();
        $lifecycle = app(SeoAuditRunLifecycle::class);
        $run = $lifecycle->start(
            $lifecycle->request($admin),
        );

        $source = $this->page(
            runId: $run->getKey(),
            url: 'https://example.test/category/widgets',
            depth: 1,
            subjectType: SeoAuditSubjectType::Category,
            subjectKey: 'category:10',
            title: 'Widgets',
        );

        $target = $this->page(
            runId: $run->getKey(),
            url: 'https://example.test/products/widget-pro',
            depth: 0,
            subjectType: SeoAuditSubjectType::Product,
            subjectKey: 'product:99',
            title: 'Widget Pro',
        );

        $lifecycle->recordFinding(
            $run,
            new SeoAuditFindingData(
                rule: SeoAuditRule::OrphanProduct,
                severity: SeoAuditSeverity::Warning,
                subjectType: SeoAuditSubjectType::Product,
                subjectKey: 'product:99',
                url: $target->url,
                message: 'Published product has no observed internal links.',
                evidence: ['incoming_links' => 0],
                remediation: 'Add contextual internal links to this product.',
            ),
        );

        $run->forceFill([
            'catalog_seeded_at' => now(),
            'crawl_settled_at' => now(),
            'image_health_enriched_at' => now(),
            'evaluated_at' => now(),
        ])->save();

        $step = app(SeoAuditQueuedOrchestrator::class)->advance(
            run: $run,
            canonicalOrigin: 'https://example.test',
        );

        self::assertTrue($step->completed);

        $run->refresh();

        self::assertSame(SeoAuditRunStatus::Completed, $run->status);
        self::assertNotNull(
            $run->getAttribute('internal_links_built_at'),
        );

        $opportunity = SeoAuditInternalLinkOpportunity::query()
            ->where('seo_audit_run_id', $run->getKey())
            ->firstOrFail();

        self::assertSame($source->getKey(), $opportunity->source_page_id);
        self::assertSame($target->getKey(), $opportunity->target_page_id);
        self::assertSame(
            SeoAuditInternalLinkReviewState::Pending,
            $opportunity->review_state,
        );

        app(SeoAuditInternalLinkReviewService::class)->review(
            run: $run,
            fingerprint: $opportunity->fingerprint,
            state: SeoAuditInternalLinkReviewState::Accepted,
            admin: $admin,
            note: 'Approved for editorial placement.',
        );

        $replay = app(SeoAuditQueuedOrchestrator::class)->advance(
            run: $run->refresh(),
            canonicalOrigin: 'https://example.test',
        );

        self::assertTrue($replay->completed);
        self::assertSame(
            1,
            SeoAuditInternalLinkOpportunity::query()
                ->where('seo_audit_run_id', $run->getKey())
                ->count(),
        );
        self::assertSame(
            SeoAuditInternalLinkReviewState::Accepted,
            $opportunity->refresh()->review_state,
        );
        self::assertSame(
            0,
            SeoAuditInternalLink::query()
                ->where('seo_audit_run_id', $run->getKey())
                ->count(),
        );
    }

    private function page(
        int $runId,
        string $url,
        int $depth,
        SeoAuditSubjectType $subjectType,
        string $subjectKey,
        string $title,
    ): SeoAuditCrawlPage {
        return SeoAuditCrawlPage::query()->create([
            'seo_audit_run_id' => $runId,
            'url_hash' => hash('sha256', $url),
            'url' => $url,
            'state' => SeoAuditCrawlState::Fetched,
            'depth' => $depth,
            'subject_type' => $subjectType,
            'subject_key' => $subjectKey,
            'http_status' => 200,
            'content_type' => 'text/html; charset=UTF-8',
            'is_indexable' => true,
            'title' => $title,
            'h1_count' => 1,
            'word_count' => 300,
            'fetched_at' => now(),
        ]);
    }
}
