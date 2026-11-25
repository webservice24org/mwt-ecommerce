<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\Ai\Analysis\Enrichment;

use App\Domain\Seo\Ai\Analysis\Enrichment\Contracts\AiSeoAnalysisEnrichmentProvider;
use App\Domain\Seo\Ai\Analysis\Enrichment\Enums\AiSeoAnalysisEnrichmentStatus;
use App\Domain\Seo\Ai\Analysis\Enrichment\Exceptions\AiSeoAnalysisEnrichmentValidationException;
use App\Domain\Seo\Ai\Analysis\Enrichment\Services\AiSeoAnalysisEnrichmentService;
use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisGrade;
use App\Domain\Seo\Ai\Data\AiSeoProviderResultData;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Models\AiSeoAnalysis;
use App\Models\AiSeoAnalysisEnrichment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AiSeoAnalysisEnrichmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai-seo-analysis.max_actions' => 5,
        ]);
    }

    public function test_successful_enrichment_is_bound_to_analysis_without_mutating_score_or_live_entity(): void
    {
        $product =
            Product::factory()
                ->create([
                    'meta_title' => 'Existing Product Title',

                    'meta_description' => 'Existing product description.',
                ]);

        $analysis =
            $this->analysis(
                entityId: $product->id,
            );

        $scoreBefore =
            $analysis
                ->score;

        $enrichment =
            app(
                AiSeoAnalysisEnrichmentService::class,
            )->enrich(
                analysisId: $analysis->id,

                enrichedByAdminId: 91,

                provider: $this->validProvider(),
            );

        self::assertSame(
            AiSeoAnalysisEnrichmentStatus::Completed,
            $enrichment->status,
        );

        self::assertSame(
            $analysis->analysis_input_hash,
            $enrichment->analysis_input_hash,
        );

        self::assertSame(
            64,
            strlen(
                $enrichment->input_hash,
            ),
        );

        self::assertSame(
            'analysis-test-provider',
            $enrichment->provider,
        );

        self::assertSame(
            'analysis-test-model',
            $enrichment->model_name,
        );

        self::assertSame(
            'analysis-provider-response',
            $enrichment->provider_response_id,
        );

        self::assertSame(
            91,
            $enrichment->enriched_by_admin_id,
        );

        self::assertSame(
            'high',
            $enrichment
                ->actions[0]['priority'],
        );

        $analysis->refresh();

        self::assertSame(
            $scoreBefore,
            $analysis->score,
        );

        $product->refresh();

        self::assertSame(
            'Existing Product Title',
            $product->meta_title,
        );

        self::assertSame(
            'Existing product description.',
            $product->meta_description,
        );

        self::assertDatabaseCount(
            'ai_seo_analysis_enrichments',
            1,
        );
    }

    public function test_unknown_provider_finding_marks_enrichment_failed_and_persists_no_actions(): void
    {
        $analysis =
            $this->analysis();

        try {
            app(
                AiSeoAnalysisEnrichmentService::class,
            )->enrich(
                analysisId: $analysis->id,

                provider: $this->invalidProvider(),
            );

            self::fail(
                'Invalid AI analysis enrichment was accepted.',
            );
        } catch (
            AiSeoAnalysisEnrichmentValidationException
        ) {
            self::assertTrue(
                true,
            );
        }

        $record =
            AiSeoAnalysisEnrichment::query()
                ->firstOrFail();

        self::assertSame(
            AiSeoAnalysisEnrichmentStatus::Failed,
            $record->status,
        );

        self::assertSame(
            'invalid_analysis_enrichment_output',
            $record->error_code,
        );

        self::assertNull(
            $record->actions,
        );

        self::assertNotNull(
            $record->completed_at,
        );
    }

    public function test_analysis_with_no_findings_skips_provider_and_creates_no_enrichment_record(): void
    {
        $analysis =
            $this->analysis(
                findings: [],
            );

        $provider =
            new class implements AiSeoAnalysisEnrichmentProvider
            {
                public bool $called =
                    false;

                public function name(): string
                {
                    return 'should-not-run';
                }

                public function model(): string
                {
                    return 'should-not-run';
                }

                public function generate(
                    array $context,
                ): AiSeoProviderResultData {
                    $this->called =
                        true;

                    return new AiSeoProviderResultData(
                        payload: [],

                        rawResponse: [],
                    );
                }
            };

        try {
            app(
                AiSeoAnalysisEnrichmentService::class,
            )->enrich(
                analysisId: $analysis->id,

                provider: $provider,
            );

            self::fail(
                'Analysis without findings should not be enriched.',
            );
        } catch (
            \DomainException
        ) {
            self::assertFalse(
                $provider->called,
            );
        }

        self::assertDatabaseCount(
            'ai_seo_analysis_enrichments',
            0,
        );
    }

    /**
     * @param  list<array<string, mixed>>|null  $findings
     */
    private function analysis(
        ?int $entityId = null,
        ?array $findings = null,
    ): AiSeoAnalysis {
        $now =
            now();

        return AiSeoAnalysis::query()
            ->create([
                'entity_type' => AiSeoEntityType::Product,

                'entity_id' => $entityId
                    ?? 999,

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

                'source_snapshot' => [
                'schema_version' => 'ai-seo-entity-snapshot-v1',

                'entity' => [
                    'type' => 'product',

                    'id' => $entityId
                        ?? 999,

                    'name' => 'Test Product',

                    'slug' => 'test-product',
                ],

                'content' => [
                    'summary' => 'Known factual product content.',

                    'body' => null,
                ],

                'context' => [],

                'current_seo' => [
                    'meta_title' => null,

                    'meta_description' => null,

                    'og_title' => null,

                    'og_description' => null,

                    'twitter_title' => null,

                    'twitter_description' => null,
                ],
                ],

                'resolved_seo' => [
                'title' => 'Test Product | MWT Store',

                'description' => null,

                'canonical_override' => null,

                'robots_index' => true,

                'robots_follow' => true,

                'og_title' => 'Test Product | MWT Store',

                'og_description' => null,

                'og_image' => null,

                'twitter_title' => 'Test Product | MWT Store',

                'twitter_description' => null,

                'twitter_image' => null,
                ],

                'score' => 54,

                'grade' => AiSeoAnalysisGrade::NeedsWork,

                'breakdown' => [
                'search_metadata' => [
                    'points' => 25,

                    'max_points' => 50,
                ],

                'source_content' => [
                    'points' => 19,

                    'max_points' => 25,
                ],

                'social_coverage' => [
                    'points' => 10,

                    'max_points' => 25,
                ],
                ],

                'rules' => [],

                'findings' => $findings
                    ?? [
                    [
                        'id' => 'missing_resolved_description',

                        'category' => 'search_metadata',

                        'severity' => 'critical',

                        'title' => 'Resolved SEO description is missing',

                        'message' => 'The authoritative SEO resolver produced no description.',

                        'recommendation' => 'Add useful factual descriptive content.',
                    ],

                    [
                        'id' => 'social_image_missing',

                        'category' => 'social_coverage',

                        'severity' => 'info',

                        'title' => 'Social sharing image is missing',

                        'message' => 'No social image is resolved.',

                        'recommendation' => 'Configure an accurate social image.',
                    ],
                ],

                'analyzed_by_admin_id' => null,

                'analyzed_at' => $now,

                'created_at' => $now,
            ]);
    }

    private function validProvider(): AiSeoAnalysisEnrichmentProvider
    {
        return new class implements AiSeoAnalysisEnrichmentProvider
        {
            public function name(): string
            {
                return 'analysis-test-provider';
            }

            public function model(): string
            {
                return 'analysis-test-model';
            }

            public function generate(
                array $context,
            ): AiSeoProviderResultData {
                return new AiSeoProviderResultData(
                    payload: [
                        'summary' => 'The deterministic analysis identifies a missing search description as the highest-priority issue.',

                        'actions' => [
                            [
                                'finding_id' => 'missing_resolved_description',

                                'explanation' => 'The resolved SEO output has no search description.',

                                'action' => 'Write a factual description using only the existing product content.',
                            ],

                            [
                                'finding_id' => 'social_image_missing',

                                'explanation' => 'The resolved social metadata has no image.',

                                'action' => 'Configure an accurate product or global social image.',
                            ],
                        ],
                    ],

                    rawResponse: [
                        'id' => 'analysis-provider-response',

                        'unpersisted_extra' => 'This value must never be stored.',
                    ],
                );
            }
        };
    }

    private function invalidProvider(): AiSeoAnalysisEnrichmentProvider
    {
        return new class implements AiSeoAnalysisEnrichmentProvider
        {
            public function name(): string
            {
                return 'invalid-analysis-provider';
            }

            public function model(): string
            {
                return 'invalid-analysis-model';
            }

            public function generate(
                array $context,
            ): AiSeoProviderResultData {
                return new AiSeoProviderResultData(
                    payload: [
                        'summary' => 'Invalid output.',

                        'actions' => [
                            [
                                'finding_id' => 'invented_new_problem',

                                'explanation' => 'Invented.',

                                'action' => 'Invented.',
                            ],
                        ],
                    ],

                    rawResponse: [
                        'id' => 'invalid-response',
                    ],
                );
            }
        };
    }
}
