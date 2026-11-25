<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\AiGovernance;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SeoAiUsageRetryGovernanceContractTest extends TestCase
{
    #[Test]
    public function generation_flow_wires_reported_usage_into_success_and_failure_governance(): void
    {
        $generation = file_get_contents(
            base_path('app/Domain/Seo/Ai/Services/AiSeoSuggestionGenerationService.php'),
        );
        $bridge = file_get_contents(
            base_path('app/Domain/Seo/AiGovernance/Services/SeoAiLegacyRequestGovernance.php'),
        );
        $extractor = file_get_contents(
            base_path('app/Domain/Seo/AiGovernance/Support/SeoAiProviderUsageExtractor.php'),
        );
        $migration = file_get_contents(
            base_path('database/migrations/2026_11_25_103000_create_seo_ai_governance_attempts_table.php'),
        );

        self::assertIsString($generation);
        self::assertIsString($bridge);
        self::assertIsString($extractor);
        self::assertIsString($migration);

        self::assertStringContainsString('SeoAiProviderUsageExtractor', $generation);
        self::assertStringContainsString('->extract($result->rawResponse)', $generation);
        self::assertStringContainsString('usage: $usage', $generation);
        self::assertStringContainsString('?SeoAiUsageData $usage = null', $bridge);

        self::assertStringContainsString("'prompt_tokens'", $extractor);
        self::assertStringContainsString("'input_tokens'", $extractor);
        self::assertStringContainsString("'promptTokenCount'", $extractor);
        self::assertStringContainsString("'_mwt_usage'", $extractor);
        self::assertStringNotContainsString('price_per_', $extractor);
        self::assertStringNotContainsString('modelPrices', $extractor);

        self::assertStringContainsString("Schema::create('seo_ai_governance_attempts'", $migration);
        self::assertStringNotContainsString('raw_response', $migration);
        self::assertStringNotContainsString('failure_message', $migration);
    }
}
