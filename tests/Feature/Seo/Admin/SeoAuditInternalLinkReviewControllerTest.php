<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

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

final class SeoAuditInternalLinkReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_editor_can_accept_opportunity_without_applying_content(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);
        [$run, $opportunity] = $this->runAndOpportunity();

        $this
            ->actingAs($admin, 'admin')
            ->from(route('admin.seo.internal-links.index', [
                'run' => $run->public_id,
            ]))
            ->patch(
                route('admin.seo.internal-links.review', [
                    'run' => $run->public_id,
                    'fingerprint' => $opportunity->fingerprint,
                ]),
                [
                    'action' => 'accepted',
                    'note' => 'Editorially approved.',
                ],
            )
            ->assertRedirect()
            ->assertSessionHas('success');

        $opportunity->refresh();

        self::assertSame(
            SeoAuditInternalLinkReviewState::Accepted,
            $opportunity->review_state,
        );
        self::assertSame(
            'Editorially approved.',
            $opportunity->getAttribute('review_note'),
        );
    }

    public function test_pending_is_not_a_valid_review_action(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);
        [$run, $opportunity] = $this->runAndOpportunity();

        $this
            ->actingAs($admin, 'admin')
            ->patch(
                route('admin.seo.internal-links.review', [
                    'run' => $run->public_id,
                    'fingerprint' => $opportunity->fingerprint,
                ]),
                ['action' => 'pending'],
            )
            ->assertSessionHasErrors('action');

        self::assertSame(
            SeoAuditInternalLinkReviewState::Pending,
            $opportunity->refresh()->review_state,
        );
    }

    public function test_fingerprint_is_scoped_to_the_route_bound_run(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);
        [$run, $opportunity] = $this->runAndOpportunity();
        [$otherRun] = $this->runAndOpportunity('other');

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
    }

    /**
     * @return array{SeoAuditRun,SeoAuditInternalLinkOpportunity}
     */
    private function runAndOpportunity(string $suffix = 'default'): array
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
        ]);

        $source = $this->page($run, "https://example.test/{$suffix}/source");
        $target = $this->page($run, "https://example.test/{$suffix}/target");

        $opportunity = SeoAuditInternalLinkOpportunity::query()->create([
            'seo_audit_run_id' => $run->getKey(),
            'source_page_id' => $source->getKey(),
            'target_page_id' => $target->getKey(),
            'fingerprint' => hash('sha256', $suffix.'-fingerprint'),
            'engine_version' => 'seo-internal-links-v1',
            'review_state' => SeoAuditInternalLinkReviewState::Pending,
            'score' => 90,
            'target_subject_type' => SeoAuditSubjectType::Product,
            'target_subject_key' => 'product:99',
            'source_url' => $source->url,
            'target_url' => $target->url,
            'suggested_anchor' => 'Widget Pro',
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
