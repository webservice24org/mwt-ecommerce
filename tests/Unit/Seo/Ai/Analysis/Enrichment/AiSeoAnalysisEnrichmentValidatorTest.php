<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai\Analysis\Enrichment;

use App\Domain\Seo\Ai\Analysis\Enrichment\Enums\AiSeoAnalysisActionPriority;
use App\Domain\Seo\Ai\Analysis\Enrichment\Exceptions\AiSeoAnalysisEnrichmentValidationException;
use App\Domain\Seo\Ai\Analysis\Enrichment\Services\AiSeoAnalysisEnrichmentValidator;
use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisGrade;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Models\AiSeoAnalysis;
use Tests\TestCase;

final class AiSeoAnalysisEnrichmentValidatorTest extends TestCase
{
    private AiSeoAnalysisEnrichmentValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai-seo-analysis.max_actions' => 5,
        ]);

        $this->validator =
            new AiSeoAnalysisEnrichmentValidator;
    }

    public function test_valid_actions_are_bound_to_existing_findings_and_priority_is_deterministic(): void
    {
        $result =
            $this
                ->validator
                ->validate(
                    [
                        'summary' => '  The analysis has one high-impact metadata gap and one lower-priority presentation issue. ',

                        'actions' => [
                            [
                                'finding_id' => 'missing_resolved_description',

                                'explanation' => '  Search engines have less descriptive context from the resolved metadata. ',

                                'action' => '  Add a factual meta description based on the existing product content. ',
                            ],

                            [
                                'finding_id' => 'social_image_missing',

                                'explanation' => 'Social shares currently have no resolved image.',

                                'action' => 'Configure an accurate product or global social image.',
                            ],
                        ],
                    ],
                    $this->analysis(),
                );

        self::assertSame(
            'The analysis has one high-impact metadata gap and one lower-priority presentation issue.',
            $result->summary,
        );

        self::assertCount(
            2,
            $result->actions,
        );

        self::assertSame(
            AiSeoAnalysisActionPriority::High,
            $result->actions[0]->priority,
        );

        self::assertSame(
            AiSeoAnalysisActionPriority::Low,
            $result->actions[1]->priority,
        );
    }

    public function test_unknown_finding_id_is_rejected(): void
    {
        $this->expectException(
            AiSeoAnalysisEnrichmentValidationException::class,
        );

        $this
            ->validator
            ->validate(
                [
                    'summary' => 'Summary.',

                    'actions' => [
                        [
                            'finding_id' => 'invented_finding',

                            'explanation' => 'Explanation.',

                            'action' => 'Action.',
                        ],
                    ],
                ],
                $this->analysis(),
            );
    }

    public function test_duplicate_finding_id_is_rejected(): void
    {
        $this->expectException(
            AiSeoAnalysisEnrichmentValidationException::class,
        );

        $this
            ->validator
            ->validate(
                [
                    'summary' => 'Summary.',

                    'actions' => [
                        [
                            'finding_id' => 'social_image_missing',

                            'explanation' => 'First.',

                            'action' => 'First action.',
                        ],

                        [
                            'finding_id' => 'social_image_missing',

                            'explanation' => 'Second.',

                            'action' => 'Second action.',
                        ],
                    ],
                ],
                $this->analysis(),
            );
    }

    public function test_html_urls_and_unexpected_fields_are_rejected(): void
    {
        foreach (
            [
                [
                    'summary' => '<strong>Unsafe</strong>',

                    'actions' => [
                        [
                            'finding_id' => 'social_image_missing',

                            'explanation' => 'Explanation.',

                            'action' => 'Action.',
                        ],
                    ],
                ],

                [
                    'summary' => 'Summary.',

                    'actions' => [
                        [
                            'finding_id' => 'social_image_missing',

                            'explanation' => 'See https://example.com',

                            'action' => 'Action.',
                        ],
                    ],
                ],

                [
                    'summary' => 'Summary.',

                    'actions' => [
                        [
                            'finding_id' => 'social_image_missing',

                            'explanation' => 'Explanation.',

                            'action' => 'Action.',

                            'score' => 100,
                        ],
                    ],
                ],
            ] as $payload
        ) {
            try {
                $this
                    ->validator
                    ->validate(
                        $payload,
                        $this->analysis(),
                    );

                self::fail(
                    'Unsafe AI SEO analysis enrichment was accepted.',
                );
            } catch (
                AiSeoAnalysisEnrichmentValidationException
            ) {
                self::assertTrue(
                    true,
                );
            }
        }
    }

    private function analysis(): AiSeoAnalysis
    {
        $analysis =
            new AiSeoAnalysis;

        $analysis->forceFill([
            'id' => 10,

            'entity_type' => AiSeoEntityType::Product,

            'entity_id' => 20,

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

            'resolved_seo' => [],

            'score' => 55,

            'grade' => AiSeoAnalysisGrade::NeedsWork,

            'breakdown' => [],

            'rules' => [],

            'findings' => [
            [
                'id' => 'missing_resolved_description',

                'category' => 'search_metadata',

                'severity' => 'critical',

                'title' => 'Description missing',

                'message' => 'Resolved description is missing.',

                'recommendation' => 'Add a factual description.',
            ],

            [
                'id' => 'social_image_missing',

                'category' => 'social_coverage',

                'severity' => 'info',

                'title' => 'Social image missing',

                'message' => 'Social image is missing.',

                'recommendation' => 'Configure an accurate social image.',
            ],
            ],
        ]);

        return $analysis;
    }
}
