<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Workspace;

use App\Domain\Seo\Ai\Analysis\Services\AiSeoEntityAnalysisService;
use App\Domain\Seo\Ai\Enums\AiSeoAction;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Enums\AiSeoRequestStatus;
use App\Domain\Seo\Ai\Enums\AiSeoSuggestionField;
use App\Domain\Seo\Ai\Services\AiSeoEntitySnapshotBuilder;
use App\Models\AiSeoRequest;
use App\Models\AiSeoSuggestion;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AiSeoWorkspaceReleaseSecurityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_read_routes_do_not_mutate_seo_or_make_external_http_requests(): void
    {
        $this->withoutMiddleware();

        Http::fake();

        $product =
            Product::factory()
                ->create([
                    'name' => 'Read Only Workspace Product',

                    'slug' => 'read-only-workspace-product',

                    'meta_title' => 'Protected read-only title',

                    'meta_description' => 'Protected read-only description.',
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

                    'input_hash' => str_repeat(
                        'a',
                        64,
                    ),

                    'provider' => 'test-provider',

                    'model_name' => 'test-model',

                    'prompt_version' => 'ai-seo-metadata-v1',

                    'completed_at' => now(),
                ]);

        AiSeoSuggestion::query()
            ->create([
                'ai_seo_request_id' => $request->id,

                'field' => AiSeoSuggestionField::MetaTitle,

                'value' => 'Draft only title',

                'rationale' => 'Draft only.',

                'position' => 0,
            ]);

        $before = [
            'meta_title' => $product
                ->meta_title,

            'meta_description' => $product
                ->meta_description,

            'analysis_count' => 0,

            'review_count' => 0,
        ];

        $this->get(
            route(
                'admin.seo.overview',
            ),
        )->assertOk();

        $this->get(
            route(
                'admin.seo.suggestions.index',
            ),
        )->assertOk();

        $this->get(
            route(
                'admin.seo.entities.show',
                [
                    'entityType' => 'product',

                    'entityId' => $product->id,
                ],
            ),
        )->assertOk();

        $this->get(
            route(
                'admin.seo.technical-health',
            ),
        )->assertOk();

        Http::assertNothingSent();

        $product->refresh();

        self::assertSame(
            $before[
                'meta_title'
            ],
            $product
                ->meta_title,
        );

        self::assertSame(
            $before[
                'meta_description'
            ],
            $product
                ->meta_description,
        );

        self::assertDatabaseCount(
            'ai_seo_analyses',
            $before[
                'analysis_count'
            ],
        );

        self::assertDatabaseCount(
            'ai_seo_suggestion_reviews',
            $before[
                'review_count'
            ],
        );
    }

    public function test_cross_entity_analysis_enrichment_is_rejected_and_no_apply_route_exists(): void
    {
        $this->withoutMiddleware();

        $first =
            Product::factory()
                ->create();

        $second =
            Product::factory()
                ->create();

        $analysis =
            app(
                AiSeoEntityAnalysisService::class,
            )->analyze(
                AiSeoEntityType::Product,
                $first->id,
            );

        $this->post(
            route(
                'admin.seo.entities.analyses.enrich',
                [
                    'entityType' => 'product',

                    'entityId' => $second->id,

                    'analysisId' => $analysis->id,
                ],
            ),
        )->assertNotFound();

        self::assertFalse(
            Route::has(
                'admin.seo.suggestions.apply',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.entities.suggestions.apply',
            ),
        );

        self::assertFalse(
            Route::has(
                'admin.seo.entities.apply',
            ),
        );
    }
}
