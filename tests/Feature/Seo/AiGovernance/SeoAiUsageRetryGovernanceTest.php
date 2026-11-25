<?php

declare(strict_types=1);

namespace Tests\Feature\Seo\AiGovernance;

use App\Domain\Seo\AiGovernance\Data\SeoAiUsageData;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernancePurpose;
use App\Domain\Seo\AiGovernance\Enums\SeoAiGovernanceStatus;
use App\Domain\Seo\AiGovernance\Services\SeoAiGovernanceManager;
use App\Models\SeoAiGovernanceAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class SeoAiUsageRetryGovernanceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_each_terminal_attempt_and_aggregates_reported_usage_across_retry(): void
    {
        $manager = app(SeoAiGovernanceManager::class);

        $run = $manager->request(
            purpose: SeoAiGovernancePurpose::SeoSuggestion,
            provider: 'provider-a',
            model: 'model-a',
            promptVersion: 'seo-v1',
            input: ['title' => 'Governed product'],
        );

        $run = $manager->start($run);
        $run = $manager->fail(
            run: $run,
            failureCode: 'invalid_provider_output',
            safeMessage: 'AI SEO generation failed.',
            usage: new SeoAiUsageData(
                inputTokens: 100,
                outputTokens: 10,
                totalTokens: 110,
                costAmountMinor: 2,
                costCurrency: 'USD',
            ),
        );

        self::assertSame(SeoAiGovernanceStatus::Failed, $run->status);
        self::assertSame(110, $run->total_tokens);
        self::assertSame(2, $run->cost_amount_minor);

        $run = $manager->start($run);
        $run = $manager->succeed(
            run: $run,
            generatedOutput: ['title' => 'Governed SEO title'],
            usage: new SeoAiUsageData(
                inputTokens: 120,
                outputTokens: 20,
                totalTokens: 140,
                costAmountMinor: 3,
                costCurrency: 'USD',
            ),
        );

        self::assertSame(2, $run->attempt_count);
        self::assertSame(220, $run->input_tokens);
        self::assertSame(30, $run->output_tokens);
        self::assertSame(250, $run->total_tokens);
        self::assertSame(5, $run->cost_amount_minor);
        self::assertSame('USD', $run->cost_currency);

        $attempts = SeoAiGovernanceAttempt::query()
            ->where('governance_run_id', $run->getKey())
            ->orderBy('attempt_number')
            ->get();

        self::assertCount(2, $attempts);
        self::assertSame('failed', $attempts[0]->outcome);
        self::assertSame('invalid_provider_output', $attempts[0]->failure_code);
        self::assertSame(110, $attempts[0]->total_tokens);
        self::assertSame('succeeded', $attempts[1]->outcome);
        self::assertSame(140, $attempts[1]->total_tokens);
    }

    #[Test]
    public function mixed_reported_cost_currencies_are_never_combined(): void
    {
        $manager = app(SeoAiGovernanceManager::class);

        $run = $manager->request(
            purpose: SeoAiGovernancePurpose::SeoSuggestion,
            provider: 'provider-a',
            model: 'model-a',
            promptVersion: 'seo-v1',
            input: ['title' => 'Currency guard'],
        );

        $run = $manager->start($run);
        $run = $manager->fail(
            run: $run,
            failureCode: 'provider_timeout',
            usage: new SeoAiUsageData(costAmountMinor: 2, costCurrency: 'USD'),
        );

        $run = $manager->start($run);
        $run = $manager->succeed(
            run: $run,
            generatedOutput: ['title' => 'Done'],
            usage: new SeoAiUsageData(costAmountMinor: 3, costCurrency: 'EUR'),
        );

        self::assertNull($run->cost_amount_minor);
        self::assertNull($run->cost_currency);
        self::assertSame(2, SeoAiGovernanceAttempt::query()->where('governance_run_id', $run->getKey())->count());
    }
}
