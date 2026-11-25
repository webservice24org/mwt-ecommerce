<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Analysis;

use App\Domain\PageBuilder\Enums\PageContentMode;
use App\Domain\PageBuilder\Enums\PageStatus;
use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisGrade;
use App\Domain\Seo\Ai\Analysis\Services\AiSeoAnalysisPolicy;
use App\Domain\Seo\Ai\Analysis\Services\AiSeoEntityAnalysisService;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoEntityAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    private AiSeoEntityAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->saveSeoSettings();

        $this->service =
            app(
                AiSeoEntityAnalysisService::class,
            );
    }

    public function test_product_analysis_binds_snapshot_resolved_seo_score_and_actor_to_immutable_history(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Detailed Braided USB Charging Cable',

                    'slug' => 'detailed-braided-usb-charging-cable',

                    'short_description' => 'Reliable braided charging cable with practical everyday handling and durable construction.',

                    'description' => str_repeat(
                        'This cable is designed for practical everyday charging and dependable device connectivity. ',
                        4,
                    ),

                    'meta_title' => 'Detailed Braided USB Charging Cable',

                    'meta_description' => 'Explore a reliable braided USB charging cable designed for practical everyday device charging, dependable connectivity, and easy handling.',
                ]);

        $analysis =
            $this
                ->service
                ->analyze(
                    AiSeoEntityType::Product,
                    $product->id,
                    88,
                );

        self::assertSame(
            AiSeoEntityType::Product,
            $analysis->entity_type,
        );

        self::assertSame(
            $product->id,
            $analysis->entity_id,
        );

        self::assertSame(
            AiSeoAnalysisPolicy::VERSION,
            $analysis->analysis_version,
        );

        self::assertSame(
            64,
            strlen(
                $analysis->snapshot_hash,
            ),
        );

        self::assertSame(
            64,
            strlen(
                $analysis->analysis_input_hash,
            ),
        );

        self::assertSame(
            'Detailed Braided USB Charging Cable',
            $analysis
                ->source_snapshot[
                    'entity'
                ]['name'],
        );

        self::assertSame(
            88,
            $analysis->analyzed_by_admin_id,
        );

        self::assertGreaterThanOrEqual(
            90,
            $analysis->score,
        );

        self::assertSame(
            AiSeoAnalysisGrade::Excellent,
            $analysis->grade,
        );

        self::assertSame(
            'https://example.com/default-social.jpg',
            $analysis
                ->resolved_seo[
                    'og_image'
                ],
        );

        self::assertDatabaseCount(
            'ai_seo_analyses',
            1,
        );
    }

    public function test_reanalysis_appends_history_and_snapshot_hash_changes_when_source_changes(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'History Product',

                    'slug' => 'history-product',

                    'description' => 'Original source content.',
                ]);

        $first =
            $this
                ->service
                ->analyze(
                    AiSeoEntityType::Product,
                    $product->id,
                );

        $product->update([
            'description' => str_repeat(
                'Expanded factual product content for the second immutable analysis snapshot. ',
                5,
            ),
        ]);

        $second =
            $this
                ->service
                ->analyze(
                    AiSeoEntityType::Product,
                    $product->id,
                );

        self::assertNotSame(
            $first->id,
            $second->id,
        );

        self::assertNotSame(
            $first->snapshot_hash,
            $second->snapshot_hash,
        );

        self::assertNotSame(
            $first->analysis_input_hash,
            $second->analysis_input_hash,
        );

        self::assertSame(
            'Original source content.',
            $first
                ->fresh()
                ?->source_snapshot[
                    'content'
                ]['body'],
        );

        self::assertDatabaseCount(
            'ai_seo_analyses',
            2,
        );
    }

    public function test_all_h2_entity_types_are_supported_by_real_analysis_service(): void
    {
        $product =
            Product::factory()
                ->create([
                    'description' => 'Product source.',
                ]);

        $category =
            Category::factory()
                ->create([
                    'description' => 'Category source.',
                ]);

        $brand =
            Brand::factory()
                ->create([
                    'description' => 'Brand source.',
                ]);

        $page =
            Page::factory()
                ->create([
                    'status' => PageStatus::Published,

                    'content_mode' => PageContentMode::Classic,

                    'content' => '<p>Page source content.</p>',

                    'published_at' => now()
                        ->subMinute(),
                ]);

        foreach (
            [
                [
                    AiSeoEntityType::Product,
                    $product->id,
                ],
                [
                    AiSeoEntityType::Category,
                    $category->id,
                ],
                [
                    AiSeoEntityType::Brand,
                    $brand->id,
                ],
                [
                    AiSeoEntityType::Page,
                    $page->id,
                ],
            ] as [
                $entityType,
                $entityId,
            ]
        ) {
            $analysis =
                $this
                    ->service
                    ->analyze(
                        $entityType,
                        $entityId,
                    );

            self::assertSame(
                $entityType,
                $analysis->entity_type,
            );

            self::assertSame(
                $entityId,
                $analysis->entity_id,
            );
        }

        self::assertDatabaseCount(
            'ai_seo_analyses',
            4,
        );
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

                defaultMetaDescription: 'Default storefront description used when an entity does not provide a more specific description.',

                canonicalHost: 'https://example.com',

                defaultRobotsIndex: true,

                defaultRobotsFollow: true,

                defaultOgImage: 'https://example.com/default-social.jpg',

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
