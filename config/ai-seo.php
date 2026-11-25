<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AI SEO master switch
    |--------------------------------------------------------------------------
    |
    | Disabled by default. Installing 10.4H.3 must not create external,
    | billable provider requests until the operator explicitly enables it.
    |
    */
    'enabled' => (bool) env(
        'AI_SEO_ENABLED',
        false,
    ),

    /*
    |--------------------------------------------------------------------------
    | Default provider
    |--------------------------------------------------------------------------
    */
    'default' => (string) env(
        'AI_SEO_PROVIDER',
        'openai',
    ),

    'openai' => [
    'api_key' => (string) env(
        'OPENAI_API_KEY',
        '',
    ),

    'base_url' => (string) env(
        'AI_SEO_OPENAI_BASE_URL',
        'https://api.openai.com/v1',
    ),

    /*
         * Keep the model configurable.
         *
         * H.3 uses a cost-conscious default suitable for
         * short structured metadata generation.
         */
    'model' => (string) env(
        'AI_SEO_OPENAI_MODEL',
        'gpt-6-luna',
    ),

    'timeout_seconds' => (int) env(
        'AI_SEO_OPENAI_TIMEOUT',
        30,
    ),

    'max_output_tokens' => (int) env(
        'AI_SEO_OPENAI_MAX_OUTPUT_TOKENS',
        1200,
    ),
    ],
];
