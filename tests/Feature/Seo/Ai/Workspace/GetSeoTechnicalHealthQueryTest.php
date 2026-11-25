<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Workspace;

use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisGrade;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Workspace\Queries\GetSeoTechnicalHealthQuery;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\AiSeoAnalysis;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GetSeoTechnicalHealthQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_fully_configured_and_fully_analyzed_workspace_scores_one_hundred(): void
    {
        $this->saveHealthySettings();

        $product =
            Product::factory()
                ->create();

        $category =
            Category::factory()
                ->create();

        $brand =
            Brand::factory()
                ->create();

        $page =
            Page::factory()
                ->create();

        $this->analysis(
            AiSeoEntityType::Product,
            $product->id,
        );

        $this->analysis(
            AiSeoEntityType::Category,
            $category->id,
        );

        $this->analysis(
            AiSeoEntityType::Brand,
            $brand->id,
        );

        $this->analysis(
            AiSeoEntityType::Page,
            $page->id,
        );

        $health =
            app(
                GetSeoTechnicalHealthQuery::class,
            )->handle();

        self::assertSame(
            100,
            $health['summary']['health_score'],
        );

        self::assertSame(
            'excellent',
            $health['summary']['health_grade'],
        );

        self::assertSame(
            14,
            $health['summary']['passing_checks'],
        );

        self::assertSame(
            0,
            $health['summary']['warning_checks'],
        );

        self::assertSame(
            0,
            $health['summary']['failed_checks'],
        );

        self::assertSame(
            100,
            $health['summary']['analysis_coverage_percent'],
        );

        self::assertSame(
            100,
            $health['summary']['analysis_freshness_percent'],
        );

        self::assertSame(
            4,
            $health['coverage']['analyzed'],
        );

        self::assertSame(
            0,
            $health['operations']['latest_noindex_count'],
        );
    }

    public function test_missing_configuration_and_no_analysis_are_reported_as_health_risks(): void
    {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: null,

                canonicalHost: null,

                defaultRobotsIndex: false,

                defaultRobotsFollow: false,

                defaultOgImage: null,

                twitterCard: 'summary_large_image',

                organizationName: null,

                organizationLogo: null,

                organizationSocialProfiles: [],

                googleSiteVerification: null,

                bingSiteVerification: null,

                enableStructuredData: false,

                enableOpenGraph: false,

                enableTwitterCards: false,

                enableSitemap: false,
            ),
        );

        Product::factory()
            ->create();

        $health =
            app(
                GetSeoTechnicalHealthQuery::class,
            )->handle();

        self::assertLessThan(
            50,
            $health['summary']['health_score'],
        );

        self::assertSame(
            'critical',
            $health['summary']['health_grade'],
        );

        self::assertGreaterThan(
            0,
            $health['summary']['failed_checks'],
        );

        self::assertSame(
            0,
            $health['summary']['analysis_coverage_percent'],
        );

        self::assertSame(
            0,
            $health['summary']['analysis_freshness_percent'],
        );
    }

    private function saveHealthySettings(): void
    {
        app(
            SeoSettingsService::class,
        )->save(
            new SeoSettingsData(
                siteName: 'MWT Store',

                titleSeparator: '|',

                defaultTitleTemplate: '{page_title} {separator} {site_name}',

                defaultMetaDescription: 'Default factual storefront description.',

                canonicalHost: 'https://example.com',

                defaultRobotsIndex: true,

                defaultRobotsFollow: true,

                defaultOgImage: 'https://example.com/social.jpg',

                twitterCard: 'summary_large_image',

                organizationName: 'MWT Store',

                organizationLogo: 'https://example.com/logo.png',

                organizationSocialProfiles: [
                    'https://example.com/social-profile',
                ],

                googleSiteVerification: 'google-test-token',

                bingSiteVerification: null,

                enableStructuredData: true,

                enableOpenGraph: true,

                enableTwitterCards: true,

                enableSitemap: true,
            ),
        );
    }

    private function analysis(
        AiSeoEntityType $entityType,
        int $entityId,
    ): void {
        $now =
            now();

        AiSeoAnalysis::query()
            ->create([
                'entity_type' => $entityType,

                'entity_id' => $entityId,

                'analysis_version' => 'ai-seo-analysis-v1',

                'snapshot_schema_version' => 'ai-seo-entity-snapshot-v1',

                'snapshot_hash' => str_repeat(
                    'a',
                    64,
                ),

                'analysis_input_hash' => str_repeat(
                    'b',
                    64,
                ),

                'source_snapshot' => [],

                'resolved_seo' => [
                'robots_index' => true,
                ],

                'score' => 90,

                'grade' => AiSeoAnalysisGrade::Excellent,

                'breakdown' => [],

                'rules' => [],

                'findings' => [],

                'analyzed_by_admin_id' => null,

                'analyzed_at' => $now,

                'created_at' => $now,
            ]);
    }
}
