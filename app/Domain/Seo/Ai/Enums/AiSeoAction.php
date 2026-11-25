<?php

declare(strict_types=1);

namespace App\Domain\Seo\Ai\Enums;

enum AiSeoAction: string
{
    /*
     * H is suggestion generation only.
     *
     * Analysis/scoring belongs to 10.4I.
     * Bulk operations belong to 10.4K.
     */
    case GenerateMetadataSuggestions =
        'generate_metadata_suggestions';
}
