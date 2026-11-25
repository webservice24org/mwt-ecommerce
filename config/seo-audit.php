<?php

declare(strict_types=1);

return [
    'queue' => env('SEO_AUDIT_QUEUE', 'seo-audit'),
    'continuation_delay_seconds' => (int) env(
        'SEO_AUDIT_CONTINUATION_DELAY_SECONDS',
        2,
    ),
];
