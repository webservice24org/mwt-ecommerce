<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai\Analysis;

use App\Domain\Seo\Ai\Analysis\Enrichment\Enums\AiSeoAnalysisActionPriority;
use App\Domain\Seo\Ai\Analysis\Enrichment\Services\AiSeoAnalysisEnrichmentContextFactory;
use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisSeverity;
use App\Domain\Seo\Ai\Analysis\Services\AiSeoAnalysisPolicy;
use App\Models\AiSeoAnalysisEnrichment;
use Tests\TestCase;

final class AiSeoAnalysisReleaseContractTest extends TestCase
{
    public function test_release_versions_and_priority_contract_are_stable(): void
    {
        self::assertSame(
            'ai-seo-analysis-v1',
            AiSeoAnalysisPolicy::VERSION,
        );

        self::assertSame(
            'ai-seo-analysis-enrichment-v1',
            AiSeoAnalysisEnrichmentContextFactory::PROMPT_VERSION,
        );

        self::assertSame(
            AiSeoAnalysisActionPriority::High,
            AiSeoAnalysisActionPriority::fromSeverity(
                AiSeoAnalysisSeverity::Critical,
            ),
        );

        self::assertSame(
            AiSeoAnalysisActionPriority::Medium,
            AiSeoAnalysisActionPriority::fromSeverity(
                AiSeoAnalysisSeverity::Warning,
            ),
        );

        self::assertSame(
            AiSeoAnalysisActionPriority::Low,
            AiSeoAnalysisActionPriority::fromSeverity(
                AiSeoAnalysisSeverity::Info,
            ),
        );
    }

    public function test_enrichment_storage_contract_excludes_score_secrets_and_raw_provider_body(): void
    {
        $fillable =
            (new AiSeoAnalysisEnrichment)
                ->getFillable();

        foreach (
            [
                'score',
                'grade',
                'breakdown',
                'rules',
                'findings',
                'api_key',
                'raw_response',
                'canonical_url',
                'robots_index',
                'robots_follow',
            ] as $forbidden
        ) {
            self::assertNotContains(
                $forbidden,
                $fillable,
            );
        }

        self::assertContains(
            'analysis_input_hash',
            $fillable,
        );

        self::assertContains(
            'input_context',
            $fillable,
        );

        self::assertContains(
            'provider_response_id',
            $fillable,
        );
    }

    public function test_enrichment_is_disabled_by_default_in_configuration_source(): void
    {
        $source =
            file_get_contents(
                base_path(
                    'config/ai-seo-analysis.php',
                ),
            );

        self::assertIsString(
            $source,
        );

        self::assertStringContainsString(
            "'AI_SEO_ANALYSIS_ENRICHMENT_ENABLED'",
            $source,
        );

        self::assertMatchesRegularExpression(
            '/AI_SEO_ANALYSIS_ENRICHMENT_ENABLED[\s\S]{0,120}false/',
            $source,
        );
    }

    public function test_analysis_migrations_use_datetime_for_local_mysql_compatibility(): void
    {
        $analysisMigration =
            $this->migrationSource(
                '2026_11_22_214500_create_ai_seo_analyses_table.php',
            );

        $enrichmentMigration =
            $this->migrationSource(
                '2026_11_22_221500_create_ai_seo_analysis_enrichments_table.php',
            );

        self::assertMatchesRegularExpression(
            "/->dateTime\\(\\s*'analyzed_at'/",
            $analysisMigration,
        );

        self::assertMatchesRegularExpression(
            "/->dateTime\\(\\s*'created_at'/",
            $analysisMigration,
        );

        self::assertMatchesRegularExpression(
            "/->dateTime\\(\\s*'started_at'/",
            $enrichmentMigration,
        );

        self::assertMatchesRegularExpression(
            "/->dateTime\\(\\s*'completed_at'/",
            $enrichmentMigration,
        );

        self::assertMatchesRegularExpression(
            "/->dateTime\\(\\s*'created_at'/",
            $enrichmentMigration,
        );
    }

    private function migrationSource(
        string $fileName,
    ): string {
        $matches =
            glob(
                database_path(
                    'migrations/*'.$fileName,
                ),
            );

        if (! is_array($matches)) {
            self::fail(
                'Unable to enumerate migration files.',
            );
        }

        self::assertCount(
            1,
            $matches,
        );

        $source =
            file_get_contents(
                $matches[0],
            );

        if (! is_string($source)) {
            self::fail(
                'Unable to read migration source.',
            );
        }

        return $source;
    }
}
