<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Audit;

use App\Domain\Seo\Audit\Data\SeoAuditFindingData;
use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditRule;
use App\Domain\Seo\Audit\Enums\SeoAuditSeverity;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Domain\Seo\Audit\Services\SeoAuditInternalLinkOpportunityBuilder;
use App\Domain\Seo\Audit\Services\SeoAuditRunLifecycle;
use App\Models\Admin;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditInternalLinkOpportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoAuditInternalLinkOpportunityBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_orphan_finding_builds_bounded_ranked_reviewable_opportunities(): void
    {
        $admin = Admin::factory()->create();
        $lifecycle = app(SeoAuditRunLifecycle::class);
        $run = $lifecycle->start(
            $lifecycle->request($admin),
        );

        $category = $this->page(
            runId: $run->getKey(),
            url: 'https://example.test/category/widgets',
            depth: 1,
            subjectType: SeoAuditSubjectType::Category,
            subjectKey: 'category:10',
            title: 'Widgets',
        );

        $page = $this->page(
            runId: $run->getKey(),
            url: 'https://example.test/guides/widgets',
            depth: 2,
            subjectType: SeoAuditSubjectType::Page,
            subjectKey: 'page:guide-widgets',
            title: 'Widget guide',
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

        $run = $lifecycle->complete($run, 3);

        $summary = app(
            SeoAuditInternalLinkOpportunityBuilder::class,
        )->rebuild($run);

        self::assertSame(1, $summary->orphanTargets);
        self::assertSame(1, $summary->evaluatedTargets);
        self::assertSame(2, $summary->opportunities);
        self::assertSame(0, $summary->targetsWithoutCandidates);
        self::assertFalse($summary->truncated);

        $rows = SeoAuditInternalLinkOpportunity::query()
            ->where('seo_audit_run_id', $run->getKey())
            ->orderByDesc('score')
            ->orderBy('id')
            ->get();

        self::assertCount(2, $rows);
        self::assertSame($category->getKey(), $rows[0]->source_page_id);
        self::assertSame($page->getKey(), $rows[1]->source_page_id);
        self::assertSame($target->getKey(), $rows[0]->target_page_id);
        self::assertSame('Widget Pro', $rows[0]->suggested_anchor);
        self::assertGreaterThan($rows[1]->score, $rows[0]->score);

        app(
            SeoAuditInternalLinkOpportunityBuilder::class,
        )->rebuild($run);

        self::assertSame(
            2,
            SeoAuditInternalLinkOpportunity::query()
                ->where('seo_audit_run_id', $run->getKey())
                ->count(),
        );
    }

    public function test_running_audit_cannot_build_internal_link_opportunities(): void
    {
        $this->expectException(\DomainException::class);

        $admin = Admin::factory()->create();
        $lifecycle = app(SeoAuditRunLifecycle::class);
        $run = $lifecycle->start(
            $lifecycle->request($admin),
        );

        app(
            SeoAuditInternalLinkOpportunityBuilder::class,
        )->rebuild($run);
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
