<?php

declare(strict_types=1);

return [
    'queue' => (string) env(
        'AI_SEO_BULK_QUEUE',
        'ai-seo',
    ),

    'max_attempts' => max(
        1,
        (int) env(
            'AI_SEO_BULK_MAX_ATTEMPTS',
            3,
        ),
    ),

    'unique_for_seconds' => max(
        60,
        (int) env(
            'AI_SEO_BULK_UNIQUE_FOR',
            3600,
        ),
    ),

    'processing_stale_seconds' => max(
        60,
        (int) env(
            'AI_SEO_BULK_PROCESSING_STALE_SECONDS',
            900,
        ),
    ),

    'backoff_seconds' => [
        30,
        120,
        300,
    ],
];
