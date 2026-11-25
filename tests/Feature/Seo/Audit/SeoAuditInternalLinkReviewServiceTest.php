<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Audit;

use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditInternalLinkReviewState;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Domain\Seo\Audit\Services\SeoAuditInternalLinkReviewService;
use App\Models\Admin;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditInternalLinkOpportunity;
use App\Models\SeoAuditRun;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SeoAuditInternalLinkReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_opportunity_can_be_accepted_with_reviewer_snapshot(): void
    {
        $admin = Admin::factory()->create();
        [$run, $opportunity] = $this->completedRunWithOpportunity();

        $reviewed = app(
            SeoAuditInternalLinkReviewService::class,
        )->review(
            run: $run,
            fingerprint: $opportunity->fingerprint,
            state: SeoAuditInternalLinkReviewState::Accepted,
            admin: $admin,
            note: 'Use this on the buying guide.',
        );

        self::assertSame(
            SeoAuditInternalLinkReviewState::Accepted,
            $reviewed->review_state,
        );
        self::assertSame(
            $admin->getKey(),
            $reviewed->getAttribute('reviewed_by_admin_id'),
        );
        self::assertSame(
            $admin->name,
            $reviewed->getAttribute('reviewed_by_name'),
        );
        self::assertSame(
            'Use this on the buying guide.',
            $reviewed->getAttribute('review_note'),
        );
        self::assertNotNull(
            $reviewed->getAttribute('reviewed_at'),
        );

        $again = app(
            SeoAuditInternalLinkReviewService::class,
        )->review(
            run: $run,
            fingerprint: $opportunity->fingerprint,
            state: SeoAuditInternalLinkReviewState::Accepted,
            admin: $admin,
            note: 'This must not rewrite the original review.',
        );

        self::assertSame(
            'Use this on the buying guide.',
            $again->getAttribute('review_note'),
        );
    }

    public function test_final_decision_cannot_be_changed_to_the_other_state(): void
    {
        $this->expectException(DomainException::class);

        $admin = Admin::factory()->create();
        [$run, $opportunity] = $this->completedRunWithOpportunity();
        $service = app(SeoAuditInternalLinkReviewService::class);

        $service->review(
            $run,
            $opportunity->fingerprint,
            SeoAuditInternalLinkReviewState::Accepted,
            $admin,
            null,
        );

        $service->review(
            $run,
            $opportunity->fingerprint,
            SeoAuditInternalLinkReviewState::Dismissed,
            $admin,
            null,
        );
    }

    public function test_non_completed_run_cannot_be_reviewed(): void
    {
        $this->expectException(DomainException::class);

        $admin = Admin::factory()->create();
        [$run, $opportunity] = $this->completedRunWithOpportunity();

        $run->forceFill([
            'status' => SeoAuditRunStatus::Running,
            'completed_at' => null,
        ])->save();

        app(SeoAuditInternalLinkReviewService::class)->review(
            $run,
            $opportunity->fingerprint,
            SeoAuditInternalLinkReviewState::Dismissed,
            $admin,
            null,
        );
    }

    /**
     * @return array{SeoAuditRun,SeoAuditInternalLinkOpportunity}
     */
    private function completedRunWithOpportunity(): array
    {
        $admin = Admin::factory()->create();
        $run = SeoAuditRun::query()->create([
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

        $source = $this->page($run, 'https://example.test/category/widgets');
        $target = $this->page($run, 'https://example.test/products/widget-pro');

        $opportunity = SeoAuditInternalLinkOpportunity::query()->create([
            'seo_audit_run_id' => $run->getKey(),
            'source_page_id' => $source->getKey(),
            'target_page_id' => $target->getKey(),
            'fingerprint' => hash('sha256', 'source-to-target'),
            'engine_version' => 'seo-internal-links-v1',
            'review_state' => SeoAuditInternalLinkReviewState::Pending,
            'score' => 90,
            'target_subject_type' => SeoAuditSubjectType::Product,
            'target_subject_key' => 'product:99',
            'source_url' => $source->url,
            'target_url' => $target->url,
            'suggested_anchor' => 'Widget Pro',
            'reason' => 'Strong category source for orphan product.',
        ]);

        return [$run, $opportunity];
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
}
