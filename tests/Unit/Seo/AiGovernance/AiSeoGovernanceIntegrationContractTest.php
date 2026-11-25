<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\AiGovernance;

use PHPUnit\Framework\TestCase;

final class AiSeoGovernanceIntegrationContractTest extends TestCase
{
    public function test_existing_ai_seo_generation_is_governed_without_automatic_publication(): void
    {
        $root = dirname(__DIR__, 4);

        $generation = file_get_contents(
            $root.'/app/Domain/Seo/Ai/Services/AiSeoSuggestionGenerationService.php',
        );
        self::assertIsString($generation);

        $bridge = file_get_contents(
            $root.'/app/Domain/Seo/AiGovernance/Services/SeoAiLegacyRequestGovernance.php',
        );
        self::assertIsString($bridge);

        self::assertStringContainsString('SeoAiLegacyRequestGovernance $governance', $generation);
        self::assertStringContainsString('->begin(', $generation);
        self::assertStringContainsString('->generate(', $generation);
        self::assertStringContainsString('->succeed(', $generation);
        self::assertStringContainsString('->fail(', $generation);

        $begin = strpos($generation, '->begin(');
        $generate = strpos($generation, '->generate(');
        $governedSuccess = strpos($generation, '->succeed(');
        $legacyComplete = strpos($generation, '->complete(');

        self::assertIsInt($begin);
        self::assertIsInt($generate);
        self::assertIsInt($governedSuccess);
        self::assertIsInt($legacyComplete);
        self::assertTrue($begin < $generate);
        self::assertTrue($governedSuccess < $legacyComplete);

        self::assertStringContainsString('SeoAiGovernancePurpose::SeoSuggestion', $bridge);
        self::assertStringContainsString("safeMessage: 'AI SEO generation failed.'", $bridge);

        self::assertStringNotContainsString('rawResponse:', $bridge);
        self::assertStringNotContainsString('markPublished(', $generation);
        self::assertStringNotContainsString('approve(', $generation);
        self::assertStringNotContainsString('Product::query()->update', $generation);
    }
}
