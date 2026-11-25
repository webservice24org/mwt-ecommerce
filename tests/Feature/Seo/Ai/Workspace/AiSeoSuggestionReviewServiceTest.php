<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Workspace;

use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Domain\Seo\Ai\Workspace\Enums\AiSeoSuggestionReviewDecision;
use App\Domain\Seo\Ai\Workspace\Services\AiSeoSuggestionReviewService;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\Product;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoSuggestionReviewServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_suggestion_can_be_approved_and_review_history_is_append_only(): void
    {
        [
            $product,
            $suggestion,
        ] =
            $this->suggestion();

        $service =
            app(
                AiSeoSuggestionReviewService::class,
            );

        $approved =
            $service->approve(
                $suggestion,
                44,
                'Looks accurate.',
            );

        self::assertSame(
            AiSeoSuggestionReviewDecision::Approved,
            $approved->decision,
        );

        self::assertSame(
            'fresh',
            $approved->freshness_state,
        );

        self::assertSame(
            44,
            $approved->reviewed_by_admin_id,
        );

        self::assertSame(
            'Looks accurate.',
            $approved->note,
        );

        $rejected =
            $service->reject(
                $suggestion,
                45,
                'Reconsidered.',
            );

        self::assertSame(
            AiSeoSuggestionReviewDecision::Rejected,
            $rejected->decision,
        );

        self::assertDatabaseCount(
            'ai_seo_suggestion_reviews',
            2,
        );

        $suggestion->refresh();

        self::assertSame(
            AiSeoSuggestionReviewDecision::Rejected,
            $suggestion
                ->latestReview
                ?->decision,
        );

        $product->refresh();

        self::assertNull(
            $product->meta_title,
        );
    }

    public function test_stale_suggestion_cannot_be_approved_but_can_be_rejected(): void
    {
        [
            $product,
            $suggestion,
        ] =
            $this->suggestion();

        $product->update([
            'description' => 'Entity changed after generation.',
        ]);

        $service =
            app(
                AiSeoSuggestionReviewService::class,
            );

        try {
            $service->approve(
                $suggestion,
            );

            self::fail(
                'Stale suggestion was approved.',
            );
        } catch (
            DomainException $exception
        ) {
            self::assertStringContainsString(
                'stale',
                strtolower(
                    $exception
                        ->getMessage(),
                ),
            );
        }

        self::assertDatabaseCount(
            'ai_seo_suggestion_reviews',
            0,
        );

        $rejected =
            $service->reject(
                $suggestion,
            );

        self::assertSame(
            AiSeoSuggestionReviewDecision::Rejected,
            $rejected->decision,
        );

        self::assertSame(
            'stale',
            $rejected->freshness_state,
        );
    }

    /**
     * @return array{0: Product, 1: AiSeoSuggestion}
     */
    private function suggestion(): array
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Review Product',

                    'slug' => 'review-product',

                    'description' => 'Stable factual content.',
                ]);

        $snapshot =
            app(
                AiSeoEntitySnapshotBuilder::class,
            )
                ->build(
                    AiSeoEntityType::Product,
                    $product->id,
                )
                ->toArray();

        $request =
            AiSeoRequest::query()
                ->create([
                    'entity_type' => AiSeoEntityType::Product,

                    'entity_id' => $product->id,

                    'action' => AiSeoAction::GenerateMetadataSuggestions,

                    'status' => AiSeoRequestStatus::Completed,

                    'requested_by_admin_id' => 10,

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

        $suggestion =
            AiSeoSuggestion::query()
                ->create([
                    'ai_seo_request_id' => $request->id,

                    'field' => AiSeoSuggestionField::MetaTitle,

                    'value' => 'Suggested Product SEO Title',

                    'rationale' => 'Clear factual title.',

                    'position' => 0,
                ]);

        return [
            $product,
            $suggestion,
        ];
    }
}
