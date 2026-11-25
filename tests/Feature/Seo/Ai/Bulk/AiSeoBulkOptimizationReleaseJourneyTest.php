<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Bulk;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Queries\GetAiSeoBulkApplicationWorkspaceQuery;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkApplicationService;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkPlanService;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkSuggestionReviewService;
use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Domain\Seo\Ai\Workspace\Enums\AiSeoSuggestionReviewDecision;
use App\Models\Admin;
use App\Models\AiSeoBulkJobItem;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesGovernedSeoSuggestionFixtures;
use Tests\TestCase;

final class AiSeoBulkOptimizationReleaseJourneyTest extends TestCase
{
    use CreatesGovernedSeoSuggestionFixtures;
    use RefreshDatabase;

    public function test_k1_through_k4_release_journey_preserves_plan_review_and_application_audit(): void
    {
        foreach ([501, 502, 503, 504] as $adminId) {
            Admin::factory()->create(['id' => $adminId]);
        }

        $product =
            Product::factory()
                ->create([
                    'name' => 'K5 Release Product',

                    'slug' => 'k5-release-product',

                    'meta_title' => 'Original release title',

                    'description' => 'Stable release source content.',
                ]);

        $product
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/k5-protected-canonical',

                'robots_index' => false,

                'robots_follow' => true,

                'og_title' => 'Original release OG title',

                'og_image' => 'https://example.com/k5-protected-og.jpg',

                'twitter_image' => 'https://example.com/k5-protected-twitter.jpg',
            ]);

        /*
         * K.1 — immutable dry-run planning.
         */
        $plan =
            app(
                AiSeoBulkPlanService::class,
            )->create(
                targets: [
                    [
                        'entity_type' => 'product',

                        'entity_id' => $product->id,
                    ],
                ],

                filters: [
                    'state' => 'eligible',
                ],

                plannedByAdminId: 501,
            );

        $originalPlanHash =
            $plan
                ->plan_hash;

        /*
         * K.2 integration boundary.
         *
         * Provider generation itself is covered by the focused K.2
         * tests. The release journey seeds the normal completed H.1
         * request/suggestion state and links it to the K.1 item.
         */
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

                    'requested_by_admin_id' => 501,

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

        $metaTitle =
            AiSeoSuggestion::query()
                ->create([
                    'ai_seo_request_id' => $request->id,

                    'field' => AiSeoSuggestionField::from(
                        'meta_title',
                    ),

                    'value' => 'Applied release title',

                    'rationale' => 'K.5 release title rationale.',

                    'position' => 0,
                ]);

        $ogTitle =
            AiSeoSuggestion::query()
                ->create([
                    'ai_seo_request_id' => $request->id,

                    'field' => AiSeoSuggestionField::from(
                        'og_title',
                    ),

                    'value' => 'Applied release OG title',

                    'rationale' => 'K.5 release OG rationale.',

                    'position' => 1,
                ]);

        $this->governSeoFixture($request);

        $item =
            AiSeoBulkJobItem::query()
                ->where(
                    'ai_seo_bulk_job_id',
                    $plan->id,
                )
                ->firstOrFail();

        $item->update([
            'execution_status' => AiSeoBulkItemExecutionStatus::Completed,

            'attempt_count' => 1,

            'ai_seo_request_id' => $request->id,

            'started_at' => now(),

            'last_attempt_at' => now(),

            'completed_at' => now(),
        ]);

        /*
         * K.3 — explicit human review only.
         */
        $reviewBatch =
            app(
                AiSeoBulkSuggestionReviewService::class,
            )->review(
                job: $plan,

                suggestionIds: [
                    $metaTitle->id,
                    $ogTitle->id,
                ],

                decision: AiSeoSuggestionReviewDecision::Approved,

                reviewedByAdminId: 502,

                note: 'K.5 release approval.',
            );

        self::assertSame(
            2,
            $reviewBatch
                ->summary[
                    'approved'
                ],
        );

        self::assertDatabaseCount(
            'ai_seo_suggestion_reviews',
            2,
        );

        self::assertDatabaseCount(
            'ai_seo_bulk_review_batches',
            1,
        );

        self::assertDatabaseCount(
            'ai_seo_bulk_review_batch_items',
            2,
        );

        /*
         * Approval still does not apply live SEO.
         */
        $product->refresh();
        $product->load('seoMeta');

        self::assertSame(
            'Original release title',
            $product
                ->meta_title,
        );

        self::assertSame(
            'Original release OG title',
            $product
                ->seoMeta
                ?->og_title,
        );

        /*
         * K.4 — guarded application.
         */
        $applyBatch =
            app(
                AiSeoBulkApplicationService::class,
            )->apply(
                job: $plan,

                suggestionIds: [
                    $metaTitle->id,
                    $ogTitle->id,
                ],

                appliedByAdminId: 503,

                note: 'K.5 guarded release application.',
            );

        self::assertSame(
            2,
            $applyBatch
                ->summary[
                    'applied'
                ],
        );

        self::assertSame(
            0,
            $applyBatch
                ->summary[
                    'conflict'
                ],
        );

        self::assertSame(
            0,
            $applyBatch
                ->summary[
                    'stale'
                ],
        );

        $product->refresh();
        $product->load('seoMeta');

        self::assertSame(
            'Applied release title',
            $product
                ->meta_title,
        );

        self::assertSame(
            'Applied release OG title',
            $product
                ->seoMeta
                ?->og_title,
        );

        /*
         * Protected SEO remains unchanged.
         */
        self::assertSame(
            'https://example.com/k5-protected-canonical',
            $product
                ->seoMeta
                ?->canonical_url,
        );

        self::assertFalse(
            $product
                ->seoMeta
                ?->robots_index,
        );

        self::assertTrue(
            $product
                ->seoMeta
                ?->robots_follow,
        );

        self::assertSame(
            'https://example.com/k5-protected-og.jpg',
            $product
                ->seoMeta
                ?->og_image,
        );

        self::assertSame(
            'https://example.com/k5-protected-twitter.jpg',
            $product
                ->seoMeta
                ?->twitter_image,
        );

        self::assertDatabaseCount(
            'ai_seo_suggestion_applications',
            2,
        );

        self::assertDatabaseCount(
            'ai_seo_bulk_apply_batches',
            1,
        );

        self::assertDatabaseCount(
            'ai_seo_bulk_apply_batch_items',
            2,
        );

        /*
         * Duplicate K.4 submission is idempotent.
         */
        $secondApply =
            app(
                AiSeoBulkApplicationService::class,
            )->apply(
                job: $plan,

                suggestionIds: [
                    $metaTitle->id,
                    $ogTitle->id,
                ],

                appliedByAdminId: 504,
            );

        self::assertSame(
            2,
            $secondApply
                ->summary[
                    'already_applied'
                ],
        );

        self::assertDatabaseCount(
            'ai_seo_suggestion_applications',
            2,
        );

        self::assertDatabaseCount(
            'ai_seo_suggestion_reviews',
            2,
        );

        /*
         * K.1 plan identity never changes during K.2/K.3/K.4.
         */
        $plan->refresh();

        self::assertSame(
            $originalPlanHash,
            $plan
                ->plan_hash,
        );

        /*
         * Application workspace reconciles ledger state.
         */
        $workspace =
            app(
                GetAiSeoBulkApplicationWorkspaceQuery::class,
            )->handle(
                $plan->id,
            );

        self::assertSame(
            2,
            $workspace[
                'summary'
            ][
                'already_applied'
            ],
        );

        self::assertSame(
            0,
            $workspace[
                'summary'
            ][
                'ready'
            ],
        );
    }
}
