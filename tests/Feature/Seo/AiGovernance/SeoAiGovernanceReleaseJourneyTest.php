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
use App\Models\SeoAiGovernanceAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SeoAiGovernanceReleaseJourneyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function governed_ai_journey_preserves_retry_usage_human_review_publication_and_audit_history(): void
    {
        $requester = Admin::factory()->create();
        $reviewer = Admin::factory()->create();
        $manager = app(SeoAiGovernanceManager::class);
        $rawInputMarker = 'RAW-INPUT-MARKER-DO-NOT-AUDIT';

        $run = $manager->request(
            purpose: SeoAiGovernancePurpose::SeoSuggestion,
            provider: 'provider-a',
            model: 'model-a',
            promptVersion: 'seo-closure-v1',
            input: [
                'entity_type' => 'product',
                'entity_key' => '42',
                'title' => $rawInputMarker,
                'description' => 'Public catalog description.',
                'public_url' => 'https://example.test/products/42',
            ],
            subjectType: 'product',
            subjectKey: '42',
            requester: $requester,
        );

        self::assertSame('provider-a', $run->provider);
        self::assertSame('model-a', $run->model_name);
        self::assertSame('seo-closure-v1', $run->prompt_version);
        self::assertSame(64, strlen($run->input_snapshot_hash));
        self::assertSame(SeoAiGovernanceStatus::Queued, $run->status);
        self::assertSame(SeoAiApprovalStatus::PendingReview, $run->approval_status);
        self::assertArrayNotHasKey('input_payload', $run->getAttributes());
        self::assertArrayNotHasKey('raw_input', $run->getAttributes());

        $run = $manager->start($run);
        $run = $manager->fail(
            run: $run,
            failureCode: 'provider_timeout',
            safeMessage: 'Authorization: Bearer closure-secret-token',
            usage: new SeoAiUsageData(
                inputTokens: 100,
                outputTokens: 10,
                totalTokens: 110,
                costAmountMinor: 2,
                costCurrency: 'USD',
            ),
        );

        self::assertSame('AI generation failed.', $run->failure_message);
        self::assertSame(SeoAiGovernanceStatus::Failed, $run->status);

        $run = $manager->start($run);
        $run = $manager->succeed(
            run: $run,
            generatedOutput: [
                'title' => 'Governed SEO title',
                'meta_description' => 'Governed SEO description.',
            ],
            usage: new SeoAiUsageData(
                inputTokens: 120,
                outputTokens: 20,
                totalTokens: 140,
                costAmountMinor: 3,
                costCurrency: 'USD',
            ),
        );

        $run = $manager->approve($run, $reviewer);
        self::assertNull($run->published_at, 'Human approval must not publish AI output.');

        $run = $manager->markPublished($run, $reviewer);

        self::assertSame(SeoAiGovernanceStatus::Succeeded, $run->status);
        self::assertSame(SeoAiApprovalStatus::Approved, $run->approval_status);
        self::assertNotNull($run->generated_at);
        self::assertNotNull($run->reviewed_at);
        self::assertNotNull($run->published_at);
        self::assertSame(2, $run->attempt_count);
        self::assertSame(220, $run->input_tokens);
        self::assertSame(30, $run->output_tokens);
        self::assertSame(250, $run->total_tokens);
        self::assertSame(5, $run->cost_amount_minor);
        self::assertSame('USD', $run->cost_currency);
        self::assertSame('Governed SEO title', $run->generated_output['title'] ?? null);
        self::assertSame($reviewer->getKey(), $run->reviewed_by_admin_id);

        $attempts = SeoAiGovernanceAttempt::query()
            ->where('governance_run_id', $run->getKey())
            ->orderBy('attempt_number')
            ->get();

        self::assertCount(2, $attempts);
        self::assertSame('failed', $attempts[0]->outcome);
        self::assertSame('provider_timeout', $attempts[0]->failure_code);
        self::assertSame(110, $attempts[0]->total_tokens);
        self::assertSame('succeeded', $attempts[1]->outcome);
        self::assertSame(140, $attempts[1]->total_tokens);

        $events = $run->events()->get();

        self::assertSame([
            SeoAiGovernanceEventType::Requested,
            SeoAiGovernanceEventType::Started,
            SeoAiGovernanceEventType::Failed,
            SeoAiGovernanceEventType::Started,
            SeoAiGovernanceEventType::Succeeded,
            SeoAiGovernanceEventType::Approved,
            SeoAiGovernanceEventType::Published,
        ], $events->pluck('event_type')->all());

        $eventJson = json_encode($events->toArray(), JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString($rawInputMarker, $eventJson);
        self::assertStringNotContainsString('closure-secret-token', $eventJson);
    }

    #[Test]
    public function rejected_output_cannot_be_published(): void
    {
        $reviewer = Admin::factory()->create();
        $manager = app(SeoAiGovernanceManager::class);

        $run = $manager->request(
            purpose: SeoAiGovernancePurpose::SeoSuggestion,
            provider: 'provider-a',
            model: 'model-a',
            promptVersion: 'seo-closure-v1',
            input: ['title' => 'Review boundary'],
        );

        $run = $manager->start($run);
        $run = $manager->succeed($run, ['title' => 'Candidate title']);
        $run = $manager->reject($run, $reviewer);

        self::assertSame(SeoAiApprovalStatus::Rejected, $run->approval_status);
        self::assertNull($run->published_at);

        $this->expectException(ValidationException::class);
        $manager->markPublished($run, $reviewer);
    }
}
