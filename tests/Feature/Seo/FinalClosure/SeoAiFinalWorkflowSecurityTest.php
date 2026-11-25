<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\FinalClosure;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Domain\Seo\AiGovernance\Enums\SeoAiApprovalStatus;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernanceEventType;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernancePurpose;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernanceStatus;
use App\Domain\Seo\AiGovernance\Queries\GetSeoAiGovernanceAuditWorkspaceQuery;
use App\Domain\Seo\AiGovernance\Services\SeoAiGovernanceManager;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SeoAiFinalWorkflowSecurityTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function governed_ai_release_journey_requires_human_review_before_publication_and_keeps_audit_history(): void
    {
        $requester = Admin::factory()->create();
        $reviewer = Admin::factory()->create();

        $manager = app(SeoAiGovernanceManager::class);

        $rawInputMarker = 'FINAL-CLOSURE-RAW-INPUT-MUST-NOT-BE-AUDITED';

        $run = $manager->request(
            purpose: SeoAiGovernancePurpose::SeoSuggestion,
            provider: 'provider-final',
            model: 'model-final',
            promptVersion: 'seo-final-closure-v1',
            input: [
                'entity_type' => 'product',
                'entity_key' => 'final-42',
                'title' => $rawInputMarker,
                'description' => 'Public catalog description.',
                'public_url' => 'https://example.test/products/final-42',
            ],
            subjectType: 'product',
            subjectKey: 'final-42',
            requester: $requester,
        );

        self::assertSame(SeoAiGovernanceStatus::Queued, $run->status);
        self::assertSame(SeoAiApprovalStatus::PendingReview, $run->approval_status);
        self::assertArrayNotHasKey('raw_input', $run->getAttributes());
        self::assertArrayNotHasKey('input_payload', $run->getAttributes());

        $run = $manager->start($run);

        $run = $manager->succeed(
            run: $run,
            generatedOutput: [
                'title' => 'Final governed SEO title',
                'meta_description' => 'Final governed SEO description.',
            ],
        );

        $run = $manager->approve($run, $reviewer);

        self::assertSame(SeoAiApprovalStatus::Approved, $run->approval_status);
        self::assertNull(
            $run->published_at,
            'Approval must remain distinct from publication.',
        );

        $run = $manager->markPublished($run, $reviewer);

        self::assertNotNull($run->published_at);
        self::assertSame(SeoAiGovernanceStatus::Succeeded, $run->status);

        $events = $run->events()->get();

        self::assertSame([
            SeoAiGovernanceEventType::Requested,
            SeoAiGovernanceEventType::Started,
            SeoAiGovernanceEventType::Succeeded,
            SeoAiGovernanceEventType::Approved,
            SeoAiGovernanceEventType::Published,
        ], $events->pluck('event_type')->all());

        $eventJson = json_encode(
            $events->toArray(),
            JSON_THROW_ON_ERROR,
        );

        self::assertStringNotContainsString(
            $rawInputMarker,
            $eventJson,
        );
    }

    #[Test]
    public function rejected_ai_output_cannot_cross_the_publication_boundary(): void
    {
        $reviewer = Admin::factory()->create();

        $manager = app(SeoAiGovernanceManager::class);

        $run = $manager->request(
            purpose: SeoAiGovernancePurpose::SeoSuggestion,
            provider: 'provider-final',
            model: 'model-final',
            promptVersion: 'seo-final-closure-v1',
            input: [
                'title' => 'Rejected candidate',
            ],
        );

        $run = $manager->start($run);
        $run = $manager->succeed($run, [
            'title' => 'Rejected SEO title',
        ]);
        $run = $manager->reject($run, $reviewer);

        self::assertSame(SeoAiApprovalStatus::Rejected, $run->approval_status);
        self::assertNull($run->published_at);

        $this->expectException(
            ValidationException::class,
        );

        $manager->markPublished(
            $run,
            $reviewer,
        );
    }

    #[Test]
    public function governance_audit_workspace_remains_authenticated_and_raw_input_is_not_searchable(): void
    {
        $this
            ->get(route('admin.seo.ai-governance.index'))
            ->assertRedirect(route('admin.login'));

        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $marker = 'FINAL-CLOSURE-PRIVATE-SEARCH-MARKER';

        $run = app(SeoAiGovernanceManager::class)->request(
            purpose: SeoAiGovernancePurpose::SeoSuggestion,
            provider: 'provider-final',
            model: 'model-final',
            promptVersion: 'seo-final-closure-v1',
            input: [
                'entity_type' => 'product',
                'entity_key' => '99',
                'title' => $marker,
            ],
            subjectType: 'product',
            subjectKey: '99',
            requester: $admin,
        );

        $workspace = app(
            GetSeoAiGovernanceAuditWorkspaceQuery::class,
        )->get(
            runPublicId: null,
            status: null,
            approvalStatus: null,
            purpose: null,
            provider: null,
            search: $marker,
            dateFrom: null,
            dateTo: null,
            page: 1,
        );

        self::assertSame(
            0,
            $workspace['summary']['total'],
        );

        $detail = app(
            GetSeoAiGovernanceAuditWorkspaceQuery::class,
        )->get(
            runPublicId: $run->public_id,
            status: null,
            approvalStatus: null,
            purpose: null,
            provider: null,
            search: null,
            dateFrom: null,
            dateTo: null,
            page: 1,
        );

        self::assertArrayNotHasKey(
            'raw_input',
            $detail['selected_run'],
        );

        self::assertArrayNotHasKey(
            'input_payload',
            $detail['selected_run'],
        );
    }
}
