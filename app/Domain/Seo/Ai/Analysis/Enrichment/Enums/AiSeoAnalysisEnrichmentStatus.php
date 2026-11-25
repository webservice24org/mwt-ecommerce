<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Analysis\Enrichment\Enums;

enum AiSeoAnalysisEnrichmentStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Failed = 'failed';
}
