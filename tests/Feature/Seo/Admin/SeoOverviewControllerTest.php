<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisGrade;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Models\AiSeoAnalysis;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SeoOverviewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_admin_seo_overview_dashboard_page(): void
    {
        /*
         * This test owns the controller/Inertia contract only.
         * Authentication/authorization is covered separately
         * by the existing admin route security suite.
         */
        $this->withoutMiddleware();

        $product =
            Product::factory()->create();

        AiSeoAnalysis::query()->create([
            'entity_type' => AiSeoEntityType::Product,
            'entity_id' => $product->id,
            'analysis_version' => 'ai-seo-analysis-v1',
            'snapshot_schema_version' => 'ai-seo-entity-snapshot-v1',
            'snapshot_hash' => str_repeat('a', 64),
            'analysis_input_hash' => str_repeat('b', 64),
            'source_snapshot' => [],
            'resolved_seo' => [],
            'score' => 91,
            'grade' => AiSeoAnalysisGrade::Excellent,
            'breakdown' => [],
            'rules' => [],
            'findings' => [],
            'analyzed_by_admin_id' => null,
            'analyzed_at' => now(),
            'created_at' => now(),
        ]);

        $response =
            $this->get(
                route(
                    'admin.seo.overview',
                ),
            );

        $response
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component(
                        'Admin/Seo/Overview',
                    )
                    ->where(
                        'dashboard.summary.tracked_entities',
                        1,
                    )
                    ->has(
                        'dashboard.charts.score_bands',
                        4,
                    )
                    ->has(
                        'dashboard.filters.available_entity_types',
                        5,
                    ),
            );
    }
}
