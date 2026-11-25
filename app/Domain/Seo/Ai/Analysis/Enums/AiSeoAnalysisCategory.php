<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Enums;

enum AiSeoAnalysisCategory: string
{
    case SearchMetadata =
        'search_metadata';

    case SourceContent =
        'source_content';

    case SocialCoverage =
        'social_coverage';
}
