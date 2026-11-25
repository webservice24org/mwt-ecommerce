<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\AiGovernance;

use App\Domain\Seo\AiGovernance\Support\SeoAiProviderUsageExtractor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SeoAiProviderUsageExtractorTest extends TestCase
{
    #[Test]
    public function it_extracts_common_provider_token_shapes_without_estimating_cost(): void
    {
        $extractor = new SeoAiProviderUsageExtractor;

        $openAi = $extractor->extract([
            'usage' => [
                'prompt_tokens' => 120,
                'completion_tokens' => 20,
                'total_tokens' => 140,
            ],
        ]);

        self::assertNotNull($openAi);
        self::assertSame(120, $openAi->inputTokens);
        self::assertSame(20, $openAi->outputTokens);
        self::assertSame(140, $openAi->totalTokens);
        self::assertNull($openAi->costAmountMinor);
        self::assertNull($openAi->costCurrency);

        $anthropic = $extractor->extract([
            'usage' => [
                'input_tokens' => 80,
                'output_tokens' => 12,
            ],
        ]);

        self::assertNotNull($anthropic);
        self::assertSame(92, $anthropic->totalTokens);

        $gemini = $extractor->extract([
            'usageMetadata' => [
                'promptTokenCount' => 60,
                'candidatesTokenCount' => 9,
                'totalTokenCount' => 69,
            ],
        ]);

        self::assertNotNull($gemini);
        self::assertSame(60, $gemini->inputTokens);
        self::assertSame(9, $gemini->outputTokens);
        self::assertSame(69, $gemini->totalTokens);
    }

    #[Test]
    public function it_accepts_cost_only_from_explicit_amount_and_currency_metadata(): void
    {
        $extractor = new SeoAiProviderUsageExtractor;

        $usage = $extractor->extract([
            'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
            '_mwt_usage' => [
                'cost_amount_minor' => 7,
                'cost_currency' => 'usd',
            ],
        ]);

        self::assertNotNull($usage);
        self::assertSame(15, $usage->totalTokens);
        self::assertSame(7, $usage->costAmountMinor);
        self::assertSame('USD', $usage->costCurrency);

        $incompleteCost = $extractor->extract([
            '_mwt_usage' => ['cost_amount_minor' => 9],
        ]);

        self::assertNull($incompleteCost);
    }

    #[Test]
    public function malformed_or_unbounded_usage_is_ignored_instead_of_breaking_generation(): void
    {
        $extractor = new SeoAiProviderUsageExtractor;

        self::assertNull($extractor->extract('not-json'));
        self::assertNull($extractor->extract([
            'usage' => [
                'input_tokens' => -1,
                'output_tokens' => 'not-a-number',
                'total_tokens' => '999999999999999999999999',
            ],
        ]));
    }
}
