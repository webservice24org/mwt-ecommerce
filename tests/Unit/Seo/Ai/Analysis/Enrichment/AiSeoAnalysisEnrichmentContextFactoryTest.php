<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai\Analysis\Enrichment;

use App\Domain\Seo\Ai\Analysis\Enrichment\Services\AiSeoAnalysisEnrichmentContextFactory;
use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisGrade;
use App\Domain\Seo\Ai\Enums\AiSeoEntityType;
use App\Models\AiSeoAnalysis;
use Tests\TestCase;

final class AiSeoAnalysisEnrichmentContextFactoryTest extends TestCase
{
    public function test_context_is_bound_to_analysis_and_excludes_protected_resolved_seo_fields(): void
    {
        config([
            'ai-seo-analysis.max_actions' => 5,
        ]);

        $analysis =
            new AiSeoAnalysis;

        $analysis->forceFill([
            'id' => 50,

            'entity_type' => AiSeoEntityType::Product,

            'entity_id' => 99,

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
            'entity' => [
                'type' => 'product',

                'id' => 99,

                'name' => 'Cable',

                'slug' => 'cable',
            ],
            ],

            'resolved_seo' => [
            'title' => 'Cable | Store',

            'description' => 'Cable description.',

            'canonical_override' => 'https://example.com/protected',

            'robots_index' => false,

            'robots_follow' => false,

            'og_title' => 'Cable | Store',

            'og_description' => 'Cable description.',

            'og_image' => 'https://example.com/protected.jpg',

            'twitter_title' => 'Cable | Store',

            'twitter_description' => 'Cable description.',

            'twitter_image' => 'https://example.com/protected-x.jpg',
            ],

            'score' => 70,

            'grade' => AiSeoAnalysisGrade::NeedsWork,

            'breakdown' => [
            'search_metadata' => [
                'points' => 30,

                'max_points' => 50,
            ],
            ],

            'rules' => [],

            'findings' => [
            [
                'id' => 'resolved_noindex_notice',

                'category' => 'search_metadata',

                'severity' => 'info',

                'title' => 'Page resolves to noindex',

                'message' => 'Indexing is disabled.',

                'recommendation' => 'Confirm the noindex state is intentional.',
            ],
            ],
        ]);

        $context =
            (new AiSeoAnalysisEnrichmentContextFactory)
                ->make(
                    $analysis,
                );

        self::assertSame(
            AiSeoAnalysisEnrichmentContextFactory::PROMPT_VERSION,
            $context[
                'prompt_version'
            ],
        );

        self::assertSame(
            str_repeat(
                'b',
                64,
            ),
            $context[
                'analysis_identity'
            ]['analysis_input_hash'],
        );

        self::assertSame(
            'Cable | Store',
            $context[
                'resolved_text'
            ]['title'],
        );

        $encoded =
            json_encode(
                $context,
                JSON_THROW_ON_ERROR,
            );

        self::assertStringNotContainsString(
            'canonical_override',
            $encoded,
        );

        self::assertStringNotContainsString(
            'protected.jpg',
            $encoded,
        );

        self::assertStringNotContainsString(
            'protected-x.jpg',
            $encoded,
        );

        self::assertStringNotContainsString(
            '"robots_index"',
            $encoded,
        );

        self::assertStringNotContainsString(
            '"robots_follow"',
            $encoded,
        );
    }
}
