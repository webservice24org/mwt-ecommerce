<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Ai\Analysis;

use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisGrade;
use App\Domain\Seo\Ai\Analysis\Services\AiSeoAnalysisPolicy;
use PHPUnit\Framework\TestCase;

final class AiSeoAnalysisPolicyTest extends TestCase
{
    public function test_grade_bands_are_stable_and_explicit(): void
    {
        $policy =
            new AiSeoAnalysisPolicy;

        self::assertSame(
            AiSeoAnalysisGrade::Excellent,
            $policy->grade(100),
        );

        self::assertSame(
            AiSeoAnalysisGrade::Excellent,
            $policy->grade(90),
        );

        self::assertSame(
            AiSeoAnalysisGrade::Good,
            $policy->grade(89),
        );

        self::assertSame(
            AiSeoAnalysisGrade::Good,
            $policy->grade(75),
        );

        self::assertSame(
            AiSeoAnalysisGrade::NeedsWork,
            $policy->grade(74),
        );

        self::assertSame(
            AiSeoAnalysisGrade::NeedsWork,
            $policy->grade(50),
        );

        self::assertSame(
            AiSeoAnalysisGrade::Poor,
            $policy->grade(49),
        );

        self::assertSame(
            AiSeoAnalysisGrade::Poor,
            $policy->grade(0),
        );
    }
}
