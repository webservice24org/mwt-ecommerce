<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
use App\Domain\Seo\Ai\Bulk\Services\AiSeoBulkPlanService;
use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Domain\Seo\Ai\Services\AiSeoSnapshotHasher;
use App\Models\AiSeoBulkJobItem;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AiSeoBulkReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_workspace_renders_and_bulk_approval_does_not_mutate_live_seo(): void
    {
        $this->withoutMiddleware();

        $product = Product::factory()->create([
            'name' => 'Controller Bulk Review Product',
            'meta_title' => 'Controller original title',
        ]);

        $plan = app(AiSeoBulkPlanService::class)->create([
            [
                'entity_type' => 'product',
                'entity_id' => $product->id,
            ],
        ]);

        $suggestion = $this->attachSuggestion(
            $plan->id,
            $product,
        );

        $this
            ->get(
                route(
                    'admin.seo.bulk.plans.review',
                    [
                        'bulkJobId' => $plan->id,
                    ],
                ),
            )
            ->assertOk()
            ->assertInertia(
                fn (
                    Assert $page,
                ) => $page
                    ->component('Admin/Seo/Bulk/Review')
                    ->where(
                        'workspace.plan.reference',
                        $plan->reference,
                    )
                    ->where(
                        'workspace.summary.total',
                        1,
                    )
                    ->has(
                        'workspace.suggestions',
                        1,
                    ),
            );

        $this
            ->post(
                route(
                    'admin.seo.bulk.plans.review.store',
                    [
                        'bulkJobId' => $plan->id,
                    ],
                ),
                [
                    'suggestion_ids' => [
                        $suggestion->id,
                    ],
                    'decision' => 'approved',
                    'note' => 'Controller bulk approval.',
                ],
            )
            ->assertRedirect()
            ->assertSessionHas('success');

        self::assertDatabaseHas(
            'ai_seo_suggestion_reviews',
            [
                'ai_seo_suggestion_id' => $suggestion->id,
                'decision' => 'approved',
            ],
        );

        self::assertDatabaseCount(
            'ai_seo_bulk_review_batches',
            1,
        );

        $product->refresh();

        self::assertSame(
            'Controller original title',
            $product->meta_title,
        );
    }

    private function attachSuggestion(
        int $jobId,
        Product $product,
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
            'value' => 'Controller suggested title',
            'rationale' => 'Controller test.',
            'position' => 0,
        ]);

        AiSeoBulkJobItem::query()
            ->where('ai_seo_bulk_job_id', $jobId)
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
