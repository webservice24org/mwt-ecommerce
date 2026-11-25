<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AI-assisted deterministic-analysis enrichment
    |--------------------------------------------------------------------------
    |
    | Separate from AI_SEO_ENABLED. Both switches must be enabled before
    | the real provider adapter can make an external request.
    |
    */
    'enrichment_enabled' => (bool) env(
        'AI_SEO_ANALYSIS_ENRICHMENT_ENABLED',
        false,
    ),

    'max_actions' => (int) env(
        'AI_SEO_ANALYSIS_MAX_ACTIONS',
        5,
    ),

    'max_output_tokens' => (int) env(
        'AI_SEO_ANALYSIS_MAX_OUTPUT_TOKENS',
        1600,
    ),
];
