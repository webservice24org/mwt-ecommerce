<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Services;

use App\Domain\Seo\Ai\Analysis\Enums\AiSeoAnalysisGrade;

final class AiSeoAnalysisPolicy
{
    public const VERSION =
        'ai-seo-analysis-v1';

    public const TITLE_IDEAL_MIN =
        30;

    public const TITLE_IDEAL_MAX =
        70;

    public const TITLE_ACCEPTABLE_MIN =
        20;

    public const TITLE_ACCEPTABLE_MAX =
        90;

    public const DESCRIPTION_IDEAL_MIN =
        80;

    public const DESCRIPTION_IDEAL_MAX =
        200;

    public const DESCRIPTION_ACCEPTABLE_MIN =
        50;

    public const DESCRIPTION_ACCEPTABLE_MAX =
        320;

    public const CONTENT_GOOD_MIN =
        200;

    public const CONTENT_ACCEPTABLE_MIN =
        80;

    public function grade(
        int $score,
    ): AiSeoAnalysisGrade {
        return match (true) {
            $score >= 90 => AiSeoAnalysisGrade::Excellent,

            $score >= 75 => AiSeoAnalysisGrade::Good,

            $score >= 50 => AiSeoAnalysisGrade::NeedsWork,

            default => AiSeoAnalysisGrade::Poor,
        };
    }
}
