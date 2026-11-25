<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\AiGovernance;

use App\Domain\Seo\AiGovernance\Data\SeoAiUsageData;
use App\Domain\Seo\AiGovernance\Enums\SeoAiApprovalStatus;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernanceEventType;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernancePurpose;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernanceStatus;
use App\Domain\Seo\AiGovernance\Services\SeoAiGovernanceManager;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SeoAiGovernanceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function governance_run_records_retry_usage_review_publication_and_audit_events(): void
    {
        $requester = Admin::factory()->create();
        $reviewer = Admin::factory()->create();
        $manager = app(SeoAiGovernanceManager::class);

        $run = $manager->request(
            purpose: SeoAiGovernancePurpose::SeoSuggestion,
            provider: 'provider-a',
            model: 'model-a',
            promptVersion: 'seo-title-v3',
            input: [
                'entity_type' => 'product',
                'entity_key' => 'product:42',
                'title' => 'Red Shoe',
                'description' => 'Original product copy.',
                'public_url' => 'https://example.test/products/red-shoe',
            ],
            subjectType: 'product',
            subjectKey: '42',
            requester: $requester,
        );

        self::assertSame(SeoAiGovernanceStatus::Queued, $run->status);
        self::assertSame(SeoAiApprovalStatus::PendingReview, $run->approval_status);
        self::assertSame(0, $run->attempt_count);
        self::assertSame(64, strlen($run->input_snapshot_hash));
        self::assertFalse(array_key_exists('input_payload', $run->getAttributes()));

        $run = $manager->start($run);
        self::assertSame(1, $run->attempt_count);

        $run = $manager->fail($run, 'provider_timeout', 'Provider request timed out.');
        self::assertSame(SeoAiGovernanceStatus::Failed, $run->status);

        $run = $manager->start($run);
        self::assertSame(2, $run->attempt_count);

        $run = $manager->succeed(
            $run,
            ['title' => 'Red Shoe | Example Store'],
            new SeoAiUsageData(
                inputTokens: 120,
                outputTokens: 18,
                totalTokens: 138,
                costAmountMinor: 3,
                costCurrency: 'USD',
            ),
        );

        self::assertSame(SeoAiGovernanceStatus::Succeeded, $run->status);
        self::assertSame(138, $run->total_tokens);
        self::assertSame(3, $run->cost_amount_minor);
        self::assertNotNull($run->generated_at);

        $run = $manager->approve($run, $reviewer);
        self::assertSame(SeoAiApprovalStatus::Approved, $run->approval_status);
        self::assertNotNull($run->reviewed_at);

        $run = $manager->markPublished($run, $reviewer);
        self::assertNotNull($run->published_at);

        $eventTypes = $run->events()->get()->pluck('event_type')->all();

        self::assertSame([
            SeoAiGovernanceEventType::Requested,
            SeoAiGovernanceEventType::Started,
            SeoAiGovernanceEventType::Failed,
            SeoAiGovernanceEventType::Started,
            SeoAiGovernanceEventType::Succeeded,
            SeoAiGovernanceEventType::Approved,
            SeoAiGovernanceEventType::Published,
        ], $eventTypes);
    }

    #[Test]
    public function failure_messages_fail_closed_when_they_look_like_secrets(): void
    {
        $manager = app(SeoAiGovernanceManager::class);

        $run = $manager->request(
            purpose: SeoAiGovernancePurpose::SeoAnalysis,
            provider: 'provider-a',
            model: 'model-a',
            promptVersion: 'analysis-v1',
            input: ['title' => 'Safe Product'],
        );

        $run = $manager->start($run);
        $run = $manager->fail($run, 'provider_error', 'Authorization: Bearer super-secret-token');

        self::assertSame('AI generation failed.', $run->failure_message);
    }
}
