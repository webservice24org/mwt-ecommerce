<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Admin;

use App\Domain\Seo\Ai\Analysis\Services\AiSeoEntityAnalysisService;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoEntityWorkspaceActionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveSeoSettings();
    }

    public function test_analyze_action_appends_analysis_without_mutating_entity_seo(): void
    {
        $this->withoutMiddleware();

        $product =
            Product::factory()
                ->create([
                    'name' => 'Action Workspace Product',

                    'slug' => 'action-workspace-product',

                    'meta_title' => 'Original SEO title',

                    'description' => str_repeat(
                        'Stable factual product content. ',
                        8,
                    ),
                ]);

        $response =
            $this->post(
                route(
                    'admin.seo.entities.analyze',
                    [
                        'entityType' => 'product',

                        'entityId' => $product->id,
                    ],
                ),
            );

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'success',
            );

        self::assertDatabaseHas(
            'ai_seo_analyses',
            [
                'entity_type' => AiSeoEntityType::Product
                    ->value,

                'entity_id' => $product->id,
            ],
        );

        $product->refresh();

        self::assertSame(
            'Original SEO title',
            $product->meta_title,
        );
    }

    public function test_enrich_route_rejects_analysis_from_another_entity(): void
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
    }

    private function saveSeoSettings(): void
    {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: 'Default storefront description.',

                canonicalHost: 'https://example.com',

                defaultRobotsIndex: true,

                defaultRobotsFollow: true,

                defaultOgImage: null,

                twitterCard: 'summary_large_image',

                organizationName: 'MWT Store',

                organizationLogo: null,

                organizationSocialProfiles: [],

                googleSiteVerification: null,

                bingSiteVerification: null,

                enableStructuredData: true,

                enableOpenGraph: true,

                enableTwitterCards: true,

                enableSitemap: true,
            ),
        );
    }
}
