<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Analysis;

use App\Domain\Seo\Ai\Analysis\Enrichment\Contracts\AiSeoAnalysisEnrichmentProvider;
use App\Domain\Seo\Ai\Analysis\Enrichment\Queries\AiSeoAnalysisEnrichmentHistoryQuery;
use App\Domain\Seo\Ai\Analysis\Enrichment\Services\AiSeoAnalysisEnrichmentService;
use App\Domain\Seo\Ai\Analysis\Queries\AiSeoAnalysisHistoryQuery;
use App\Domain\Seo\Ai\Analysis\Services\AiSeoEntityAnalysisService;
use App\Domain\Seo\Ai\Data\AiSeoProviderResultData;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoAnalysisReleaseJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai-seo-analysis.max_actions' => 5,
        ]);

        $this->saveSeoSettings();
    }

    public function test_analysis_enrichment_and_reanalysis_remain_immutable_and_hash_bound(): void
    {
        $product =
            Product::factory()
                ->create([
                    'name' => 'Premium Braided USB-C Charging Cable',

                    'slug' => 'premium-braided-usb-c-charging-cable',

                    'short_description' => 'A reliable braided charging cable designed for practical everyday device charging and dependable connectivity.',

                    'description' => str_repeat(
                        'This product contains factual charging-cable information for everyday use and device connectivity. ',
                        4,
                    ),

                    'meta_title' => 'Premium Braided USB-C Charging Cable',

                    'meta_description' => 'Explore a reliable braided USB-C charging cable designed for practical everyday device charging, dependable connectivity, and easy handling.',
                ]);

        $product
            ->seoMeta()
            ->create([
                'canonical_url' => 'https://example.com/protected-product-canonical',

                'robots_index' => false,

                'robots_follow' => true,

                'og_title' => null,

                'og_description' => null,

                'og_image' => null,

                'twitter_title' => null,

                'twitter_description' => null,

                'twitter_image' => null,
            ]);

        $analysisService =
            app(
                AiSeoEntityAnalysisService::class,
            );

        $first =
            $analysisService
                ->analyze(
                    AiSeoEntityType::Product,
                    $product->id,
                    17,
                );

        self::assertSame(
            95,
            $first->score,
        );

        self::assertSame(
            'excellent',
            $first
                ->grade
                ->value,
        );

        self::assertSame(
            17,
            $first->analyzed_by_admin_id,
        );

        $findingIds =
            array_map(
                static fn (
                    array $finding,
                ): string => (string) $finding[
                        'id'
                    ],
                $first->findings,
            );

        self::assertContains(
            'social_image_missing',
            $findingIds,
        );

        self::assertContains(
            'resolved_noindex_notice',
            $findingIds,
        );

        $firstScore =
            $first->score;

        $firstGrade =
            $first->grade;

        $firstSnapshotHash =
            $first->snapshot_hash;

        $firstInputHash =
            $first->analysis_input_hash;

        $firstFindings =
            $first->findings;

        $firstBreakdown =
            $first->breakdown;

        $firstResolvedSeo =
            $first->resolved_seo;

        $enrichment =
            app(
                AiSeoAnalysisEnrichmentService::class,
            )->enrich(
                analysisId: $first->id,

                enrichedByAdminId: 18,

                provider: $this->safeProvider(),
            );

        self::assertSame(
            $first->id,
            $enrichment->ai_seo_analysis_id,
        );

        self::assertSame(
            $firstInputHash,
            $enrichment->analysis_input_hash,
        );

        self::assertSame(
            18,
            $enrichment->enriched_by_admin_id,
        );

        self::assertSame(
            'release-journey-provider',
            $enrichment->provider,
        );

        self::assertSame(
            'release-journey-model',
            $enrichment->model_name,
        );

        self::assertSame(
            'release-response-1',
            $enrichment->provider_response_id,
        );

        self::assertCount(
            2,
            $enrichment->actions
            ?? [],
        );

        $encodedContext =
            json_encode(
                $enrichment->input_context,
                JSON_THROW_ON_ERROR,
            );

        self::assertStringNotContainsString(
            'protected-product-canonical',
            $encodedContext,
        );

        self::assertStringNotContainsString(
            '"canonical_override"',
            $encodedContext,
        );

        self::assertStringNotContainsString(
            '"robots_index"',
            $encodedContext,
        );

        self::assertStringNotContainsString(
            '"robots_follow"',
            $encodedContext,
        );

        self::assertStringNotContainsString(
            '"og_image"',
            $encodedContext,
        );

        self::assertStringNotContainsString(
            '"twitter_image"',
            $encodedContext,
        );

        $first->refresh();

        self::assertSame(
            $firstScore,
            $first->score,
        );

        self::assertSame(
            $firstGrade,
            $first->grade,
        );

        self::assertSame(
            $firstSnapshotHash,
            $first->snapshot_hash,
        );

        self::assertSame(
            $firstInputHash,
            $first->analysis_input_hash,
        );

        self::assertSame(
            $firstFindings,
            $first->findings,
        );

        self::assertSame(
            $firstBreakdown,
            $first->breakdown,
        );

        self::assertSame(
            $firstResolvedSeo,
            $first->resolved_seo,
        );

        $product->refresh();

        self::assertSame(
            'Premium Braided USB-C Charging Cable',
            $product->meta_title,
        );

        self::assertSame(
            'Explore a reliable braided USB-C charging cable designed for practical everyday device charging, dependable connectivity, and easy handling.',
            $product->meta_description,
        );

        $seo =
            $product
                ->seoMeta()
                ->firstOrFail();

        self::assertSame(
            'https://example.com/protected-product-canonical',
            $seo->canonical_url,
        );

        self::assertFalse(
            (bool) $seo->robots_index,
        );

        /*
         * Change source/SEO and analyze again.
         * A new history row must be appended; the first analysis
         * and its enrichment stay bound to their original hash.
         */
        $product->update([
            'description' => 'Changed source content.',

            'meta_description' => 'Short.',
        ]);

        $second =
            $analysisService
                ->analyze(
                    AiSeoEntityType::Product,
                    $product->id,
                    19,
                );

        self::assertNotSame(
            $first->id,
            $second->id,
        );

        self::assertNotSame(
            $firstSnapshotHash,
            $second->snapshot_hash,
        );

        self::assertNotSame(
            $firstInputHash,
            $second->analysis_input_hash,
        );

        self::assertLessThan(
            $firstScore,
            $second->score,
        );

        self::assertSame(
            $firstInputHash,
            $enrichment
                ->fresh()
                ?->analysis_input_hash,
        );

        self::assertSame(
            $second->id,
            app(
                AiSeoAnalysisHistoryQuery::class,
            )
                ->latest(
                    AiSeoEntityType::Product,
                    $product->id,
                )
                ?->id,
        );

        self::assertSame(
            $enrichment->id,
            app(
                AiSeoAnalysisEnrichmentHistoryQuery::class,
            )
                ->latest(
                    $first->id,
                )
                ?->id,
        );

        self::assertDatabaseCount(
            'ai_seo_analyses',
            2,
        );

        self::assertDatabaseCount(
            'ai_seo_analysis_enrichments',
            1,
        );
    }

    private function safeProvider(): AiSeoAnalysisEnrichmentProvider
    {
        return new class implements AiSeoAnalysisEnrichmentProvider
        {
            public function name(): string
            {
                return 'release-journey-provider';
            }

            public function model(): string
            {
                return 'release-journey-model';
            }

            public function generate(
                array $context,
            ): AiSeoProviderResultData {
                return new AiSeoProviderResultData(
                    payload: [
                        'summary' => 'The deterministic analysis is strong overall, with two informational items to review.',

                        'actions' => [
                            [
                                'finding_id' => 'social_image_missing',

                                'explanation' => 'The resolved social metadata has no sharing image.',

                                'action' => 'Configure an accurate product or global social image.',
                            ],

                            [
                                'finding_id' => 'resolved_noindex_notice',

                                'explanation' => 'The current authoritative policy resolves this page as noindex.',

                                'action' => 'Confirm that the noindex state is intentional for this product.',
                            ],
                        ],
                    ],

                    rawResponse: [
                        'id' => 'release-response-1',

                        'raw_body_marker' => 'must-not-be-persisted',
                    ],
                );
            }
        };
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

                defaultMetaDescription: null,

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
