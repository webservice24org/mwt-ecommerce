<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Bulk;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkReviewBatchStatus;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkPlanService;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkSuggestionReviewService;
use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Domain\Seo\Ai\Workspace\Enums\AiSeoSuggestionReviewDecision;
use App\Models\AiSeoBulkJob;
use App\Models\AiSeoBulkJobItem;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\Product;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoBulkSuggestionReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_approval_is_partial_stale_safe_and_idempotent(): void
    {
        $fresh = Product::factory()->create([
            'meta_title' => 'Fresh original title',
        ]);

        $stale = Product::factory()->create([
            'meta_title' => 'Stale original title',
            'description' => 'Original description.',
        ]);

        $plan = app(AiSeoBulkPlanService::class)->create([
            [
                'entity_type' => 'product',
                'entity_id' => $fresh->id,
            ],
            [
                'entity_type' => 'product',
                'entity_id' => $stale->id,
            ],
        ]);

        $freshSuggestion = $this->attachSuggestion(
            $plan,
            $fresh,
            'Fresh suggested title',
        );

        $staleSuggestion = $this->attachSuggestion(
            $plan,
            $stale,
            'Stale suggested title',
        );

        $stale->update([
            'description' => 'Changed after suggestion generation.',
        ]);

        $service = app(
            AiSeoBulkSuggestionReviewService::class,
        );

        $batch = $service->review(
            job: $plan,
            suggestionIds: [
                $freshSuggestion->id,
                $staleSuggestion->id,
            ],
            decision: AiSeoSuggestionReviewDecision::Approved,
            reviewedByAdminId: 55,
            note: 'Bulk approval test.',
        );

        self::assertSame(
            AiSeoBulkReviewBatchStatus::CompletedWithIssues,
            $batch->status,
        );

        self::assertSame(1, $batch->summary['approved']);
        self::assertSame(1, $batch->summary['blocked_stale']);
        self::assertSame(0, $batch->summary['failed']);

        self::assertDatabaseCount(
            'ai_seo_suggestion_reviews',
            1,
        );

        self::assertDatabaseHas(
            'ai_seo_suggestion_reviews',
            [
                'ai_seo_suggestion_id' => $freshSuggestion->id,
                'decision' => 'approved',
            ],
        );

        $fresh->refresh();
        $stale->refresh();

        self::assertSame('Fresh original title', $fresh->meta_title);
        self::assertSame('Stale original title', $stale->meta_title);

        $repeat = $service->review(
            job: $plan,
            suggestionIds: [
                $freshSuggestion->id,
            ],
            decision: AiSeoSuggestionReviewDecision::Approved,
            reviewedByAdminId: 56,
        );

        self::assertSame(1, $repeat->summary['already_decided']);
        self::assertDatabaseCount(
            'ai_seo_suggestion_reviews',
            1,
        );

        $fresh->update([
            'description' => 'Changed after the original approval.',
        ]);

        $staleReapprove = $service->review(
            job: $plan,
            suggestionIds: [
                $freshSuggestion->id,
            ],
            decision: AiSeoSuggestionReviewDecision::Approved,
            reviewedByAdminId: 58,
        );

        self::assertSame(
            1,
            $staleReapprove->summary['blocked_stale'],
        );

        self::assertDatabaseCount(
            'ai_seo_suggestion_reviews',
            1,
        );

        $rejectStale = $service->review(
            job: $plan,
            suggestionIds: [
                $staleSuggestion->id,
            ],
            decision: AiSeoSuggestionReviewDecision::Rejected,
            reviewedByAdminId: 57,
        );

        self::assertSame(1, $rejectStale->summary['rejected']);
        self::assertDatabaseHas(
            'ai_seo_suggestion_reviews',
            [
                'ai_seo_suggestion_id' => $staleSuggestion->id,
                'decision' => 'rejected',
            ],
        );
    }

    public function test_cross_plan_suggestion_is_rejected_before_batch_creation(): void
    {
        $first = Product::factory()->create();
        $second = Product::factory()->create();

        $firstPlan = app(AiSeoBulkPlanService::class)->create([
            [
                'entity_type' => 'product',
                'entity_id' => $first->id,
            ],
        ]);

        $secondPlan = app(AiSeoBulkPlanService::class)->create([
            [
                'entity_type' => 'product',
                'entity_id' => $second->id,
            ],
        ]);

        $foreignSuggestion = $this->attachSuggestion(
            $secondPlan,
            $second,
            'Foreign plan title',
        );

        $this->expectException(DomainException::class);

        try {
            app(
                AiSeoBulkSuggestionReviewService::class,
            )->review(
                job: $firstPlan,
                suggestionIds: [
                    $foreignSuggestion->id,
                ],
                decision: AiSeoSuggestionReviewDecision::Approved,
            );
        } finally {
            self::assertDatabaseCount(
                'ai_seo_bulk_review_batches',
                0,
            );
        }
    }

    private function attachSuggestion(
        AiSeoBulkJob $job,
        Product $product,
        string $value,
    ): AiSeoSuggestion {
        $snapshot = app(
            AiSeoEntitySnapshotBuilder::class,
        )
            ->build(
                AiSeoEntityType::Product,
                $product->id,
            )
            ->toArray();

        $request = AiSeoRequest::query()->create([
            'entity_type' => AiSeoEntityType::Product,
            'entity_id' => $product->id,
            'action' => AiSeoAction::GenerateMetadataSuggestions,
            'status' => AiSeoRequestStatus::Completed,
            'requested_by_admin_id' => null,
            'input_snapshot' => [
                'entity_snapshot' => $snapshot,
            ],
            'input_hash' => app(
                AiSeoSnapshotHasher::class,
            )->hash([
                'entity_snapshot' => $snapshot,
            ]),
            'provider' => 'test-provider',
            'model_name' => 'test-model',
            'prompt_version' => 'ai-seo-metadata-v1',
            'completed_at' => now(),
        ]);

        $suggestion = AiSeoSuggestion::query()->create([
            'ai_seo_request_id' => $request->id,
            'field' => AiSeoSuggestionField::MetaTitle,
            'value' => $value,
            'rationale' => 'Bulk review service test.',
            'position' => 0,
        ]);

        AiSeoBulkJobItem::query()
            ->where('ai_seo_bulk_job_id', $job->id)
            ->where('entity_type', AiSeoEntityType::Product->value)
            ->where('entity_id', $product->id)
            ->update([
                'execution_status' => AiSeoBulkItemExecutionStatus::Completed,
                'ai_seo_request_id' => $request->id,
                'completed_at' => now(),
            ]);

        return $suggestion->refresh();
    }
}
