<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Seo\Ai\Bulk\Enums\AiSeoBulkItemExecutionStatus;
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
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesGovernedSeoSuggestionFixtures;
use Tests\TestCase;

final class AiSeoBulkApplicationControllerTest extends TestCase
{
    use CreatesGovernedSeoSuggestionFixtures;
    use RefreshDatabase;

    public function test_application_workspace_renders_guarded_preview(): void
    {
        $this->withoutMiddleware();
        $this->actingAs(Admin::factory()->superAdmin()->create(), 'admin');

        $product =
            Product::factory()
                ->create([
                    'meta_title' => 'Controller baseline',
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
            $this->attachApprovedSuggestion(
                $plan,
                $product,
                'Controller applied title',
            );

        $this
            ->get(
                route(
                    'admin.seo.bulk.plans.apply',
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
                    ->component(
                        'Admin/Seo/Bulk/Apply',
                    )
                    ->where(
                        'workspace.plan.id',
                        $plan->id,
                    )
                    ->where(
                        'workspace.summary.ready',
                        1,
                    )
                    ->where(
                        'workspace.suggestions.0.id',
                        $suggestion->id,
                    )
                    ->where(
                        'workspace.suggestions.0.application_status',
                        'ready',
                    )
                    ->where(
                        'workspace.suggestions.0.can_apply',
                        true,
                    ),
            );
    }

    public function test_application_route_writes_only_approved_selected_field(): void
    {
        $this->withoutMiddleware();
        $this->actingAs(Admin::factory()->superAdmin()->create(), 'admin');

        $product =
            Product::factory()
                ->create([
                    'meta_title' => 'Controller original title',
                ]);

        $product
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/controller-protected',

                'robots_index' => false,

                'robots_follow' => true,
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
            $this->attachApprovedSuggestion(
                $plan,
                $product,
                'Controller live title',
            );

        $this
            ->post(
                route(
                    'admin.seo.bulk.plans.apply.store',
                    [
                        'bulkJobId' => $plan->id,
                    ],
                ),
                [
                    'suggestion_ids' => [
                        $suggestion->id,
                    ],

                    'note' => 'Controller application test.',
                ],
            )
            ->assertRedirect()
            ->assertSessionHas(
                'success',
            );

        $product->refresh();
        $product->load('seoMeta');

        self::assertSame(
            'Controller live title',
            $product
                ->meta_title,
        );

        self::assertSame(
            'https://example.com/controller-protected',
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

        self::assertDatabaseHas(
            'ai_seo_suggestion_applications',
            [
                'ai_seo_suggestion_id' => $suggestion->id,

                'applied_value' => 'Controller live title',
            ],
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

                    'rationale' => 'Controller test.',

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
