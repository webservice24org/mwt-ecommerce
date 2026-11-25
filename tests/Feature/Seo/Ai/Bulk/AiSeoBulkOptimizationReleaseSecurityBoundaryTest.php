<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Bulk;

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
use App\Models\AiSeoBulkJob;
use App\Models\AiSeoBulkJobItem;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\Product;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AiSeoBulkOptimizationReleaseSecurityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_target_field_change_is_a_conflict_and_human_value_wins(): void
    {
        $product =
            Product::factory()
                ->create([
                    'meta_title' => 'Generation baseline title',
                ]);

        $plan =
            $this->planFor(
                $product,
            );

        $suggestion =
            $this->completedSuggestion(
                $plan,
                $product,
                'meta_title',
                'AI conflict title',
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
    }

    public function test_unrelated_entity_change_makes_old_approved_suggestion_stale(): void
    {
        $product =
            Product::factory()
                ->create([
                    'meta_title' => 'Stable target title',

                    'description' => 'Generation source.',
                ]);

        $plan =
            $this->planFor(
                $product,
            );

        $suggestion =
            $this->completedSuggestion(
                $plan,
                $product,
                'meta_title',
                'AI stale title',
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
            'Stable target title',
            $product
                ->meta_title,
        );

        self::assertDatabaseCount(
            'ai_seo_suggestion_applications',
            0,
        );
    }

    public function test_pending_or_rejected_suggestion_cannot_be_applied(): void
    {
        $product =
            Product::factory()
                ->create([
                    'meta_title' => 'Original approval boundary title',
                ]);

        $plan =
            $this->planFor(
                $product,
            );

        $pending =
            $this->completedSuggestion(
                $plan,
                $product,
                'meta_title',
                'Pending AI title',
            );

        $pendingBatch =
            app(
                AiSeoBulkApplicationService::class,
            )->apply(
                job: $plan,

                suggestionIds: [
                    $pending->id,
                ],
            );

        self::assertSame(
            1,
            $pendingBatch
                ->summary[
                    'not_approved'
                ],
        );

        app(
            AiSeoBulkSuggestionReviewService::class,
        )->review(
            job: $plan,

            suggestionIds: [
                $pending->id,
            ],

            decision: AiSeoSuggestionReviewDecision::Rejected,

            reviewedByAdminId: 601,
        );

        $rejectedBatch =
            app(
                AiSeoBulkApplicationService::class,
            )->apply(
                job: $plan,

                suggestionIds: [
                    $pending->id,
                ],
            );

        self::assertSame(
            1,
            $rejectedBatch
                ->summary[
                    'not_approved'
                ],
        );

        $product->refresh();

        self::assertSame(
            'Original approval boundary title',
            $product
                ->meta_title,
        );
    }

    public function test_cross_plan_suggestion_is_rejected_before_any_application_batch_is_created(): void
    {
        $first =
            Product::factory()
                ->create();

        $second =
            Product::factory()
                ->create();

        $firstPlan =
            $this->planFor(
                $first,
            );

        $secondPlan =
            $this->planFor(
                $second,
            );

        $foreignSuggestion =
            $this->completedSuggestion(
                $secondPlan,
                $second,
                'meta_title',
                'Foreign plan title',
            );

        $this->approve(
            $secondPlan,
            $foreignSuggestion,
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

    public function test_release_routes_expose_no_force_apply_or_review_bypass_alias(): void
    {
        self::assertFalse(
            Route::has(
                'admin.seo.bulk.plans.apply.force',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.bulk.force-apply',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.bulk.plans.auto-approve',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.bulk.auto-approve',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.bulk.rollback',
            ),
        );
    }

    private function planFor(
        Product $product,
    ): AiSeoBulkJob {
        return app(
            AiSeoBulkPlanService::class,
        )->create([
            [
                'entity_type' => 'product',

                'entity_id' => $product->id,
            ],
        ]);
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

            reviewedByAdminId: 602,
        );
    }

    private function completedSuggestion(
        AiSeoBulkJob $plan,
        Product $product,
        string $field,
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

                    'field' => AiSeoSuggestionField::from(
                        $field,
                    ),

                    'value' => $value,

                    'rationale' => 'K.5 security boundary test.',

                    'position' => 0,
                ]);

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

                'attempt_count' => 1,

                'ai_seo_request_id' => $request->id,

                'completed_at' => now(),
            ]);

        return $suggestion
            ->refresh();
    }
}
