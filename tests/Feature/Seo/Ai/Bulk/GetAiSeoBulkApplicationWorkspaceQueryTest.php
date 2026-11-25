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
use App\Models\AiSeoBulkJob;
use App\Models\AiSeoBulkJobItem;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesGovernedSeoSuggestionFixtures;
use Tests\TestCase;

final class GetAiSeoBulkApplicationWorkspaceQueryTest extends TestCase
{
    use CreatesGovernedSeoSuggestionFixtures;
    use RefreshDatabase;

    public function test_workspace_classifies_ready_conflict_and_already_applied_suggestions(): void
    {
        $readyProduct =
            Product::factory()
                ->create([
                    'meta_title' => 'Ready baseline',
                ]);

        $conflictProduct =
            Product::factory()
                ->create([
                    'meta_title' => 'Conflict baseline',
                ]);

        $appliedProduct =
            Product::factory()
                ->create([
                    'meta_title' => 'Applied baseline',
                ]);

        $plan =
            app(
                AiSeoBulkPlanService::class,
            )->create([
                [
                    'entity_type' => 'product',

                    'entity_id' => $readyProduct->id,
                ],
                [
                    'entity_type' => 'product',

                    'entity_id' => $conflictProduct->id,
                ],
                [
                    'entity_type' => 'product',

                    'entity_id' => $appliedProduct->id,
                ],
            ]);

        $ready =
            $this->attachApprovedSuggestion(
                $plan,
                $readyProduct,
                'Ready AI title',
            );

        $conflict =
            $this->attachApprovedSuggestion(
                $plan,
                $conflictProduct,
                'Conflict AI title',
            );

        $applied =
            $this->attachApprovedSuggestion(
                $plan,
                $appliedProduct,
                'Applied AI title',
            );

        $conflictProduct->update([
            'meta_title' => 'Manual conflict title',
        ]);

        app(
            AiSeoBulkApplicationService::class,
        )->apply(
            job: $plan,

            suggestionIds: [
                $applied->id,
            ],
        );

        $workspace =
            app(
                GetAiSeoBulkApplicationWorkspaceQuery::class,
            )->handle(
                $plan->id,
            );

        $rows =
            collect(
                $workspace[
                    'suggestions'
                ],
            )
                ->keyBy(
                    'id',
                );

        self::assertSame(
            'ready',
            $rows[
                $ready->id
            ]['application_status'],
        );

        self::assertTrue(
            $rows[
                $ready->id
            ]['can_apply'],
        );

        self::assertSame(
            'conflict',
            $rows[
                $conflict->id
            ]['application_status'],
        );

        self::assertFalse(
            $rows[
                $conflict->id
            ]['can_apply'],
        );

        self::assertSame(
            'already_applied',
            $rows[
                $applied->id
            ]['application_status'],
        );

        self::assertSame(
            1,
            $workspace[
                'summary'
            ]['ready'],
        );

        self::assertSame(
            1,
            $workspace[
                'summary'
            ]['conflict'],
        );

        self::assertSame(
            1,
            $workspace[
                'summary'
            ]['already_applied'],
        );
    }

    private function attachApprovedSuggestion(
        AiSeoBulkJob $job,
        Product $product,
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

                    'field' => AiSeoSuggestionField::MetaTitle,

                    'value' => $value,

                    'rationale' => 'K.4 workspace query test.',

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

        app(
            AiSeoBulkSuggestionReviewService::class,
        )->review(
            job: $job,

            suggestionIds: [
                $suggestion->id,
            ],

            decision: AiSeoSuggestionReviewDecision::Approved,

            reviewedByAdminId: Admin::factory()->create()->id,
        );

        return $suggestion
            ->refresh();
    }
}
