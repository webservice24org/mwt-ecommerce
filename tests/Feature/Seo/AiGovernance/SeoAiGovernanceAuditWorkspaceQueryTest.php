<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\AiGovernance;

use App\Domain\Seo\AiGovernance\Data\SeoAiUsageData;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernancePurpose;
use App\Domain\Seo\AiGovernance\Queries\GetSeoAiGovernanceAuditWorkspaceQuery;
use App\Domain\Seo\AiGovernance\Services\SeoAiGovernanceManager;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoAiGovernanceAuditWorkspaceQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_reports_filtered_runs_attempts_events_and_safe_detail(): void
    {
        $requester = Admin::factory()->create();
        $reviewer = Admin::factory()->create();
        $manager = app(SeoAiGovernanceManager::class);

        $run = $manager->request(
            purpose: SeoAiGovernancePurpose::SeoSuggestion,
            provider: 'provider-a',
            model: 'model-a',
            promptVersion: 'seo-v4',
            input: [
                'entity_type' => 'product',
                'entity_key' => '42',
                'title' => 'USB Cable',
            ],
            subjectType: 'product',
            subjectKey: '42',
            requester: $requester,
        );

        $run = $manager->start($run);
        $run = $manager->fail(
            $run,
            'provider_timeout',
            'AI generation failed.',
            new SeoAiUsageData(100, 0, 100, 2, 'USD'),
        );
        $run = $manager->start($run);
        $run = $manager->succeed(
            $run,
            ['title' => 'USB Cable | MWT'],
            new SeoAiUsageData(120, 20, 140, 3, 'USD'),
        );
        $run = $manager->approve($run, $reviewer);
        $run = $manager->markPublished($run, $reviewer);

        $manager->request(
            purpose: SeoAiGovernancePurpose::SeoAnalysis,
            provider: 'other-provider',
            model: 'other-model',
            promptVersion: 'analysis-v1',
            input: ['title' => 'Other'],
        );

        $workspace = app(GetSeoAiGovernanceAuditWorkspaceQuery::class)->get(
            runPublicId: $run->public_id,
            status: 'succeeded',
            approvalStatus: 'approved',
            purpose: 'seo_suggestion',
            provider: 'provider-a',
            search: '42',
            dateFrom: null,
            dateTo: null,
            page: 1,
        );

        self::assertSame(1, $workspace['summary']['total']);
        self::assertSame(1, $workspace['summary']['succeeded']);
        self::assertSame(1, $workspace['summary']['approved']);
        self::assertSame(1, $workspace['summary']['published']);
        self::assertCount(1, $workspace['runs']);
        self::assertSame($run->public_id, $workspace['selected_run']['public_id']);
        self::assertSame(['entity_key', 'entity_type', 'title'], $workspace['selected_run']['input_field_names']);
        self::assertArrayNotHasKey('input_payload', $workspace['selected_run']);
        self::assertSame(240, $workspace['selected_run']['total_tokens']);
        self::assertSame(5, $workspace['selected_run']['cost_amount_minor']);
        self::assertCount(2, $workspace['attempts']);
        self::assertSame(2, $workspace['attempts'][0]['attempt_number']);
        self::assertSame('succeeded', $workspace['attempts'][0]['outcome']);
        self::assertGreaterThanOrEqual(7, count($workspace['events']));
    }
}
