<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Audit;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Seo\Audit\Enums\SeoAuditCrawlState;
use App\Domain\Seo\Audit\Enums\SeoAuditInternalLinkReviewState;
use App\Domain\Seo\Audit\Enums\SeoAuditRunStatus;
use App\Domain\Seo\Audit\Enums\SeoAuditSubjectType;
use App\Models\Admin;
use App\Models\SeoAuditCrawlPage;
use App\Models\SeoAuditInternalLinkOpportunity;
use App\Models\SeoAuditRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SeoAuditNReleaseSecurityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_link_review_is_auth_protected_public_id_bound_and_run_scoped(): void
    {
        [$run, $opportunity] = $this->completedRunWithOpportunity('primary');

        $this
            ->get(route('admin.seo.internal-links.index'))
            ->assertRedirect(route('admin.login'));

        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->patch(
                '/admin/seo/internal-links/'.$run->getKey().'/'.$opportunity->fingerprint,
                ['action' => 'accepted'],
            )
            ->assertNotFound();

        [$otherRun] = $this->completedRunWithOpportunity('other');

        $this
            ->actingAs($admin, 'admin')
            ->patch(
                route('admin.seo.internal-links.review', [
                    'run' => $otherRun->public_id,
                    'fingerprint' => $opportunity->fingerprint,
                ]),
                ['action' => 'accepted'],
            )
            ->assertNotFound();

        self::assertSame(
            SeoAuditInternalLinkReviewState::Pending,
            $opportunity->refresh()->review_state,
        );
    }

    public function test_final_review_decision_cannot_be_reversed_through_http(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);
        [$run, $opportunity] = $this->completedRunWithOpportunity('immutable');

        $this
            ->actingAs($admin, 'admin')
            ->patch(
                route('admin.seo.internal-links.review', [
                    'run' => $run->public_id,
                    'fingerprint' => $opportunity->fingerprint,
                ]),
                [
                    'action' => 'accepted',
                    'note' => 'Approved.',
                ],
            )
            ->assertRedirect();

        $this
            ->actingAs($admin, 'admin')
            ->patch(
                route('admin.seo.internal-links.review', [
                    'run' => $run->public_id,
                    'fingerprint' => $opportunity->fingerprint,
                ]),
                [
                    'action' => 'dismissed',
                    'note' => 'Attempted reversal.',
                ],
            )
            ->assertSessionHasErrors('review');

        $opportunity->refresh();

        self::assertSame(
            SeoAuditInternalLinkReviewState::Accepted,
            $opportunity->review_state,
        );
        self::assertSame(
            'Approved.',
            $opportunity->getAttribute('review_note'),
        );
    }

    /**
     * @return array{SeoAuditRun,SeoAuditInternalLinkOpportunity}
     */
    private function completedRunWithOpportunity(string $suffix): array
    {
        $owner = Admin::factory()->create();
        $run = SeoAuditRun::query()->create([
            'public_id' => Str::uuid()->toString(),
            'requested_by_admin_id' => $owner->getKey(),
            'requested_by_name' => $owner->name,
            'requested_by_email' => $owner->email,
            'status' => SeoAuditRunStatus::Completed,
            'rules_version' => 'seo-audit-rules-v1',
            'requested_at' => now()->subMinute(),
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
            'internal_links_built_at' => now(),
        ]);

        $source = $this->page(
            $run,
            "https://example.test/{$suffix}/source",
        );
        $target = $this->page(
            $run,
            "https://example.test/{$suffix}/target",
        );

        $opportunity = SeoAuditInternalLinkOpportunity::query()->create([
            'seo_audit_run_id' => $run->getKey(),
            'source_page_id' => $source->getKey(),
            'target_page_id' => $target->getKey(),
            'fingerprint' => hash('sha256', $suffix.'-fingerprint'),
            'engine_version' => 'seo-internal-links-v1',
            'review_state' => SeoAuditInternalLinkReviewState::Pending,
            'score' => 90,
            'target_subject_type' => SeoAuditSubjectType::Product,
            'target_subject_key' => 'product:'.$suffix,
            'source_url' => $source->url,
            'target_url' => $target->url,
            'suggested_anchor' => 'Target '.$suffix,
            'reason' => 'Deterministic source candidate.',
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
