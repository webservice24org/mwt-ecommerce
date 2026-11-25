<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Analysis;

use App\Domain\Seo\Ai\Analysis\Enrichment\Contracts\AiSeoAnalysisEnrichmentProvider;
use App\Domain\Seo\Ai\Analysis\Enrichment\Enums\AiSeoAnalysisEnrichmentStatus;
use App\Domain\Seo\Ai\Analysis\Enrichment\Exceptions\AiSeoAnalysisEnrichmentValidationException;
use App\Domain\Seo\Ai\Analysis\Enrichment\Services\AiSeoAnalysisEnrichmentService;
use App\Domain\Seo\Ai\Analysis\Services\AiSeoEntityAnalysisService;
use App\Domain\Seo\Ai\Data\AiSeoProviderResultData;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Domain\Seo\Ai\Exceptions\AiSeoProviderException;
use App\Domain\Seo\Data\SeoSettingsData;
use App\Domain\Seo\Services\SeoSettingsService;
use App\Models\AiSeoAnalysisEnrichment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class AiSeoAnalysisReleaseSecurityBoundaryTest extends TestCase
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

    public function test_disabled_real_provider_path_makes_no_http_request_and_preserves_deterministic_analysis(): void
    {
        $product =
            $this->product();

        $analysis =
            app(
                AiSeoEntityAnalysisService::class,
            )->analyze(
                AiSeoEntityType::Product,
                $product->id,
            );

        $scoreBefore =
            $analysis->score;

        $gradeBefore =
            $analysis->grade;

        $findingsBefore =
            $analysis->findings;

        $secret =
            'sk-release-secret-must-not-persist';

        config([
            'ai-seo.enabled' => false,

            'ai-seo-analysis.enrichment_enabled' => true,

            'ai-seo.default' => 'openai',

            'ai-seo.openai.api_key' => $secret,

            'ai-seo.openai.base_url' => 'https://api.openai.test/v1',

            'ai-seo.openai.model' => 'gpt-6-luna',
        ]);

        Http::fake();

        try {
            app(
                AiSeoAnalysisEnrichmentService::class,
            )->enrich(
                $analysis->id,
            );

            self::fail(
                'Disabled AI SEO provider unexpectedly generated enrichment.',
            );
        } catch (
            AiSeoProviderException $exception
        ) {
            self::assertSame(
                'ai_seo_disabled',
                $exception->errorCode,
            );
        }

        Http::assertNothingSent();

        $failed =
            AiSeoAnalysisEnrichment::query()
                ->firstOrFail();

        self::assertSame(
            AiSeoAnalysisEnrichmentStatus::Failed,
            $failed->status,
        );

        self::assertSame(
            'ai_seo_disabled',
            $failed->error_code,
        );

        self::assertNull(
            $failed->summary,
        );

        self::assertNull(
            $failed->actions,
        );

        self::assertNull(
            $failed->provider_response_id,
        );

        $persisted =
            json_encode(
                $failed->toArray(),
                JSON_THROW_ON_ERROR,
            );

        self::assertStringNotContainsString(
            $secret,
            $persisted,
        );

        $analysis->refresh();

        self::assertSame(
            $scoreBefore,
            $analysis->score,
        );

        self::assertSame(
            $gradeBefore,
            $analysis->grade,
        );

        self::assertSame(
            $findingsBefore,
            $analysis->findings,
        );
    }

    public function test_malicious_provider_output_cannot_replace_score_or_add_protected_changes(): void
    {
        $product =
            $this->product();

        $analysis =
            app(
                AiSeoEntityAnalysisService::class,
            )->analyze(
                AiSeoEntityType::Product,
                $product->id,
            );

        $scoreBefore =
            $analysis->score;

        $inputHashBefore =
            $analysis->analysis_input_hash;

        try {
            app(
                AiSeoAnalysisEnrichmentService::class,
            )->enrich(
                analysisId: $analysis->id,

                provider: $this->maliciousProvider(),
            );

            self::fail(
                'Malicious AI SEO analysis enrichment was accepted.',
            );
        } catch (
            AiSeoAnalysisEnrichmentValidationException
        ) {
            self::assertTrue(
                true,
            );
        }

        $failed =
            AiSeoAnalysisEnrichment::query()
                ->firstOrFail();

        self::assertSame(
            AiSeoAnalysisEnrichmentStatus::Failed,
            $failed->status,
        );

        self::assertSame(
            'invalid_analysis_enrichment_output',
            $failed->error_code,
        );

        self::assertNull(
            $failed->summary,
        );

        self::assertNull(
            $failed->actions,
        );

        self::assertNull(
            $failed->provider_response_id,
        );

        self::assertStringNotContainsString(
            'RAW-SENSITIVE-PROVIDER-BODY',
            (string) $failed->error_message,
        );

        $analysis->refresh();

        self::assertSame(
            $scoreBefore,
            $analysis->score,
        );

        self::assertSame(
            $inputHashBefore,
            $analysis->analysis_input_hash,
        );

        $product->refresh();

        self::assertSame(
            'Release Security Product',
            $product->meta_title,
        );

        self::assertSame(
            'A factual description used to verify the release security boundary remains unchanged after rejected AI output.',
            $product->meta_description,
        );
    }

    private function maliciousProvider(): AiSeoAnalysisEnrichmentProvider
    {
        return new class implements AiSeoAnalysisEnrichmentProvider
        {
            public function name(): string
            {
                return 'malicious-release-provider';
            }

            public function model(): string
            {
                return 'malicious-release-model';
            }

            public function generate(
                array $context,
            ): AiSeoProviderResultData {
                return new AiSeoProviderResultData(
                    payload: [
                        'summary' => 'Attempt to escape the deterministic contract.',

                        'score' => 100,

                        'canonical_url' => 'https://attacker.example/hijack',

                        'robots_index' => true,

                        'actions' => [
                            [
                                'finding_id' => 'social_image_missing',

                                'explanation' => 'Attempt.',

                                'action' => 'Attempt.',
                            ],
                        ],
                    ],

                    rawResponse: [
                        'id' => 'malicious-response',

                        'body' => 'RAW-SENSITIVE-PROVIDER-BODY',
                    ],
                );
            }
        };
    }

    private function product(): Product
    {
        return Product::factory()
            ->create([
                'name' => 'Release Security Product',

                'slug' => 'release-security-product',

                'short_description' => 'Known factual source content for the release boundary.',

                'description' => str_repeat(
                    'Known factual product content for deterministic SEO analysis. ',
                    4,
                ),

                'meta_title' => 'Release Security Product',

                'meta_description' => 'A factual description used to verify the release security boundary remains unchanged after rejected AI output.',
            ]);
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
