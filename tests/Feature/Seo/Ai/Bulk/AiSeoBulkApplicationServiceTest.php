<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Bulk;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkApplyBatchStatus;
use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
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
use App\Models\AiSeoBulkJob;
use App\Models\AiSeoBulkJobItem;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\Product;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesGovernedSeoSuggestionFixtures;
use Tests\TestCase;

final class AiSeoBulkApplicationServiceTest extends TestCase
{
    use CreatesGovernedSeoSuggestionFixtures;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([81, 91, 92] as $adminId) {
            Admin::factory()->create(['id' => $adminId]);
        }
    }

    public function test_approved_fresh_conflict_free_suggestion_is_applied_once_with_before_after_audit(): void
    {
        $product =
            Product::factory()
                ->create([
                    'meta_title' => 'Original SEO title',

                    'description' => 'Stable source content.',
                ]);

        $product
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/protected-canonical',

                'robots_index' => false,

                'robots_follow' => true,

                'og_image' => 'https://example.com/protected-image.jpg',
            ]);

        $plan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $product->id,
                ],
            ]);

        $suggestion =
            $this->attachSuggestion(
                $plan,
                $product,
                AiSeoSuggestionField::MetaTitle,
                'Applied AI SEO title',
            );

        $this->approve(
            $plan,
            $suggestion,
        );

        $batch =
            app(
                AiSeoBulkApplicationService::class,
            )->apply(
                job: $plan,

                suggestionIds: [
                    $suggestion->id,
                ],

                appliedByAdminId: 91,

                note: 'Approved release application.',
            );

        self::assertSame(
            AiSeoBulkApplyBatchStatus::Completed,
            $batch->status,
        );

        self::assertSame(
            1,
            $batch
                ->summary[
                    'applied'
                ],
        );

        self::assertSame(
            0,
            $batch
                ->summary[
                    'conflict'
                ],
        );

        $product->refresh();

        self::assertSame(
            'Applied AI SEO title',
            $product
                ->meta_title,
        );

        $product->load(
            'seoMeta',
        );

        self::assertSame(
            'https://example.com/protected-canonical',
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
            'https://example.com/protected-image.jpg',
            $product
                ->seoMeta
                ?->og_image,
        );

        self::assertDatabaseHas(
            'ai_seo_suggestion_applications',
            [
                'ai_seo_suggestion_id' => $suggestion->id,

                'field' => 'meta_title',

                'baseline_value' => 'Original SEO title',

                'before_value' => 'Original SEO title',

                'applied_value' => 'Applied AI SEO title',

                'applied_by_admin_id' => 91,
            ],
        );

        self::assertDatabaseHas(
            'ai_seo_bulk_apply_batch_items',
            [
                'ai_seo_suggestion_id' => $suggestion->id,

                'result' => 'applied',

                'before_value' => 'Original SEO title',

                'after_value' => 'Applied AI SEO title',
            ],
        );

        $second =
            app(
                AiSeoBulkApplicationService::class,
            )->apply(
                job: $plan,

                suggestionIds: [
                    $suggestion->id,
                ],

                appliedByAdminId: 92,
            );

        self::assertSame(
            1,
            $second
                ->summary[
                    'already_applied'
                ],
        );

        self::assertDatabaseCount(
            'ai_seo_suggestion_applications',
            1,
        );
    }

    public function test_multiple_fields_from_same_request_can_apply_in_one_batch_without_self_induced_staleness(): void
    {
        $product =
            Product::factory()
                ->create([
                    'meta_title' => 'Original multi title',

                    'meta_description' => 'Original multi description',

                    'description' => 'Stable multi-field source.',
                ]);

        $plan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $product->id,
                ],
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

        $title =
            AiSeoSuggestion::query()
                ->create([
                    'ai_seo_request_id' => $request->id,

                    'field' => AiSeoSuggestionField::MetaTitle,

                    'value' => 'Applied multi title',

                    'rationale' => 'Multi-field test title.',

                    'position' => 0,
                ]);

        $description =
            AiSeoSuggestion::query()
                ->create([
                    'ai_seo_request_id' => $request->id,

                    'field' => AiSeoSuggestionField::MetaDescription,

                    'value' => 'Applied multi description',

                    'rationale' => 'Multi-field test description.',

                    'position' => 1,
                ]);

        $this->governSeoFixture($request);

        AiSeoBulkJobItem::query()
            ->where(
                'ai_seo_bulk_job_id',
                $plan->id,
            )
            ->where(
                'entity_type',
                AiSeoEntityType::Product
                    ->value,
            )
            ->where(
                'entity_id',
                $product->id,
            )
            ->update([
                'execution_status' => AiSeoBulkItemExecutionStatus::Completed,

                'ai_seo_request_id' => $request->id,

                'completed_at' => now(),
            ]);

        app(
            AiSeoBulkSuggestionReviewService::class,
        )->review(
            job: $plan,

            suggestionIds: [
                $title->id,
                $description->id,
            ],

            decision: AiSeoSuggestionReviewDecision::Approved,

            reviewedByAdminId: 81,
        );

        $batch =
            app(
                AiSeoBulkApplicationService::class,
            )->apply(
                job: $plan,

                suggestionIds: [
                    $title->id,
                    $description->id,
                ],
            );

        self::assertSame(
            2,
            $batch
                ->summary[
                    'applied'
                ],
        );

        self::assertSame(
            0,
            $batch
                ->summary[
                    'stale'
                ],
        );

        $product->refresh();

        self::assertSame(
            'Applied multi title',
            $product
                ->meta_title,
        );

        self::assertSame(
            'Applied multi description',
            $product
                ->meta_description,
        );

        self::assertDatabaseCount(
            'ai_seo_suggestion_applications',
            2,
        );
    }

    public function test_manual_field_change_becomes_conflict_and_is_never_overwritten(): void
    {
        $product =
            Product::factory()
                ->create([
                    'meta_title' => 'Generation baseline',
                ]);

        $plan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $product->id,
                ],
            ]);

        $suggestion =
            $this->attachSuggestion(
                $plan,
                $product,
                AiSeoSuggestionField::MetaTitle,
                'AI proposed title',
            );

        $this->approve(
            $plan,
            $suggestion,
        );

        $product->update([
            'meta_title' => 'Human manual title',
        ]);

        $batch =
            app(
                AiSeoBulkApplicationService::class,
            )->apply(
                job: $plan,

                suggestionIds: [
                    $suggestion->id,
                ],
            );

        self::assertSame(
            AiSeoBulkApplyBatchStatus::CompletedWithIssues,
            $batch->status,
        );

        self::assertSame(
            1,
            $batch
                ->summary[
                    'conflict'
                ],
        );

        $product->refresh();

        self::assertSame(
            'Human manual title',
            $product
                ->meta_title,
        );

        self::assertDatabaseCount(
            'ai_seo_suggestion_applications',
            0,
        );

        self::assertDatabaseHas(
            'ai_seo_bulk_apply_batch_items',
            [
                'ai_seo_suggestion_id' => $suggestion->id,

                'result' => 'conflict',

                'baseline_value' => 'Generation baseline',

                'before_value' => 'Human manual title',

                'proposed_value' => 'AI proposed title',

                'error_code' => 'field_conflict',
            ],
        );
    }

    public function test_unrelated_source_change_marks_approved_suggestion_stale_without_live_write(): void
    {
        $product =
            Product::factory()
                ->create([
                    'meta_title' => 'Stable title',

                    'description' => 'Original source description.',
                ]);

        $plan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $product->id,
                ],
            ]);

        $suggestion =
            $this->attachSuggestion(
                $plan,
                $product,
                AiSeoSuggestionField::MetaTitle,
                'AI title from old content',
            );

        $this->approve(
            $plan,
            $suggestion,
        );

        $product->update([
            'description' => 'Changed after human approval.',
        ]);

        $batch =
            app(
                AiSeoBulkApplicationService::class,
            )->apply(
                job: $plan,

                suggestionIds: [
                    $suggestion->id,
                ],
            );

        self::assertSame(
            1,
            $batch
                ->summary[
                    'stale'
                ],
        );

        $product->refresh();

        self::assertSame(
            'Stable title',
            $product
                ->meta_title,
        );

        self::assertDatabaseCount(
            'ai_seo_suggestion_applications',
            0,
        );
    }

    public function test_social_text_application_changes_only_selected_social_field(): void
    {
        $product =
            Product::factory()
                ->create();

        $product
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/keep',

                'robots_index' => true,

                'robots_follow' => false,

                'og_title' => 'Original OG',

                'og_image' => 'https://example.com/keep-og.jpg',

                'twitter_image' => 'https://example.com/keep-twitter.jpg',
            ]);

        $plan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $product->id,
                ],
            ]);

        $suggestion =
            $this->attachSuggestion(
                $plan,
                $product,
                AiSeoSuggestionField::from('og_title'),
                'Applied OG title',
            );

        $this->approve(
            $plan,
            $suggestion,
        );

        app(
            AiSeoBulkApplicationService::class,
        )->apply(
            job: $plan,

            suggestionIds: [
                $suggestion->id,
            ],
        );

        $product->refresh();
        $product->load('seoMeta');

        self::assertSame(
            'Applied OG title',
            $product
                ->seoMeta
                ?->og_title,
        );

        self::assertSame(
            'https://example.com/keep',
            $product
                ->seoMeta
                ?->canonical_url,
        );

        self::assertTrue(
            $product
                ->seoMeta
                ?->robots_index,
        );

        self::assertFalse(
            $product
                ->seoMeta
                ?->robots_follow,
        );

        self::assertSame(
            'https://example.com/keep-og.jpg',
            $product
                ->seoMeta
                ?->og_image,
        );

        self::assertSame(
            'https://example.com/keep-twitter.jpg',
            $product
                ->seoMeta
                ?->twitter_image,
        );
    }

    public function test_cross_plan_suggestion_is_rejected_before_application_batch_creation(): void
    {
        $first =
            Product::factory()
                ->create();

        $second =
            Product::factory()
                ->create();

        $firstPlan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $first->id,
                ],
            ]);

        $secondPlan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $second->id,
                ],
            ]);

        $foreignSuggestion =
            $this->attachSuggestion(
                $secondPlan,
                $second,
                AiSeoSuggestionField::MetaTitle,
                'Foreign plan title',
            );

        $this->expectException(
            DomainException::class,
        );

        try {
            app(
                AiSeoBulkApplicationService::class,
            )->apply(
                job: $firstPlan,

                suggestionIds: [
                    $foreignSuggestion->id,
                ],
            );
        } finally {
            self::assertDatabaseCount(
                'ai_seo_bulk_apply_batches',
                0,
            );

            self::assertDatabaseCount(
                'ai_seo_suggestion_applications',
                0,
            );
        }
    }

    private function approve(
        AiSeoBulkJob $plan,
        AiSeoSuggestion $suggestion,
    ): void {
        app(
            AiSeoBulkSuggestionReviewService::class,
        )->review(
            job: $plan,

            suggestionIds: [
                $suggestion->id,
            ],

            decision: AiSeoSuggestionReviewDecision::Approved,

            reviewedByAdminId: 81,
        );
    }

    private function attachSuggestion(
        AiSeoBulkJob $job,
        Product $product,
        AiSeoSuggestionField $field,
        string $value,
    ): AiSeoSuggestion {
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

        $suggestion =
            AiSeoSuggestion::query()
                ->create([
                    'ai_seo_request_id' => $request->id,

                    'field' => $field,

                    'value' => $value,

                    'rationale' => 'K.4 application service test.',

                    'position' => 0,
                ]);

        $this->governSeoFixture($request);

        AiSeoBulkJobItem::query()
            ->where(
                'ai_seo_bulk_job_id',
                $job->id,
            )
            ->where(
                'entity_type',
                AiSeoEntityType::Product
                    ->value,
            )
            ->where(
                'entity_id',
                $product->id,
            )
            ->update([
                'execution_status' => AiSeoBulkItemExecutionStatus::Completed,

                'ai_seo_request_id' => $request->id,

                'completed_at' => now(),
            ]);

        return $suggestion
            ->refresh();
    }
}
