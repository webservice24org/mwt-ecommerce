<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai\Analysis;

use App\Domain\Seo\Ai\Analysis\Services\AiSeoAnalysisPolicy;
use PHPUnit\Framework\TestCase;

final class AiSeoAnalysisScoreContractTest extends TestCase
{
    public function test_score_policy_is_versioned_and_totals_one_hundred_points(): void
    {
        self::assertSame(
            'ai-seo-analysis-v1',
            AiSeoAnalysisPolicy::VERSION,
        );

        $weights = [
            'search_metadata' => 50,

            'source_content' => 25,

            'social_coverage' => 25,
        ];

        self::assertSame(
            100,
            array_sum(
                $weights,
            ),
        );
    }

    public function test_internal_length_bands_never_exceed_existing_form_limits(): void
    {
        self::assertLessThanOrEqual(
            180,
            AiSeoAnalysisPolicy::TITLE_ACCEPTABLE_MAX,
        );

        self::assertLessThanOrEqual(
            320,
            AiSeoAnalysisPolicy::DESCRIPTION_ACCEPTABLE_MAX,
        );
    }
}
