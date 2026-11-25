<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Audit;

use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditInternalLinkReviewState;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Domain\Seo\Audit\Queries\GetSeoAuditInternalLinkReviewWorkspaceQuery;
use App\Models\Admin;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditInternalLinkOpportunity;
use App\Models\SeoAuditRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SeoAuditInternalLinkReviewWorkspaceQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_summary_is_global_while_rows_are_filtered(): void
    {
        $run = $this->makeRun();
        $source = $this->page($run, 'https://example.test/category/widgets');
        $target = $this->page($run, 'https://example.test/products/widget-pro');

        $this->opportunity(
            $run,
            $source,
            $target,
            'Widget Pro',
            SeoAuditInternalLinkReviewState::Pending,
            90,
        );
        $this->opportunity(
            $run,
            $source,
            $target,
            'Widget Basic',
            SeoAuditInternalLinkReviewState::Accepted,
            80,
        );
        $this->opportunity(
            $run,
            $source,
            $target,
            'Widget Lite',
            SeoAuditInternalLinkReviewState::Dismissed,
            70,
        );

        $workspace = app(
            GetSeoAuditInternalLinkReviewWorkspaceQuery::class,
        )->get(
            runPublicId: $run->public_id,
            state: SeoAuditInternalLinkReviewState::Accepted,
            search: 'Basic',
            page: 1,
        );

        self::assertSame(3, $workspace['summary']['total']);
        self::assertSame(1, $workspace['summary']['pending']);
        self::assertSame(1, $workspace['summary']['accepted']);
        self::assertSame(1, $workspace['summary']['dismissed']);
        self::assertSame(1, $workspace['pagination']['total']);
        self::assertCount(1, $workspace['opportunities']);
        self::assertSame(
            'Widget Basic',
            $workspace['opportunities'][0]['suggested_anchor'],
        );
    }

    private function makeRun(): SeoAuditRun
    {
        $admin = Admin::factory()->create();

        return SeoAuditRun::query()->create([
            'public_id' => Str::uuid()->toString(),
            'requested_by_admin_id' => $admin->getKey(),
            'requested_by_name' => $admin->name,
            'requested_by_email' => $admin->email,
            'status' => SeoAuditRunStatus::Completed,
            'rules_version' => 'seo-audit-rules-v1',
            'requested_at' => now()->subMinute(),
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
    }

    private function page(SeoAuditRun $run, string $url): SeoAuditCrawlPage
    {
        return SeoAuditCrawlPage::query()->create([
            'seo_audit_run_id' => $run->getKey(),
            'url_hash' => hash('sha256', $url),
            'url' => $url,
            'state' => SeoAuditCrawlState::Fetched,
            'depth' => 1,
            'subject_type' => SeoAuditSubjectType::Page,
            'subject_key' => 'page:'.hash('sha256', $url),
            'http_status' => 200,
            'content_type' => 'text/html',
            'is_indexable' => true,
            'title' => 'Page',
            'h1_count' => 1,
            'word_count' => 300,
            'fetched_at' => now(),
        ]);
    }

    private function opportunity(
        SeoAuditRun $run,
        SeoAuditCrawlPage $source,
        SeoAuditCrawlPage $target,
        string $anchor,
        SeoAuditInternalLinkReviewState $state,
        int $score,
    ): void {
        SeoAuditInternalLinkOpportunity::query()->create([
            'seo_audit_run_id' => $run->getKey(),
            'source_page_id' => $source->getKey(),
            'target_page_id' => $target->getKey(),
            'fingerprint' => hash('sha256', $anchor),
            'engine_version' => 'seo-internal-links-v1',
            'review_state' => $state,
            'score' => $score,
            'target_subject_type' => SeoAuditSubjectType::Product,
            'target_subject_key' => 'product:99',
            'source_url' => $source->url,
            'target_url' => $target->url,
            'suggested_anchor' => $anchor,
            'reason' => 'Deterministic source candidate.',
        ]);
    }
}
