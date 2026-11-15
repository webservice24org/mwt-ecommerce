<?php

declare(strict_types=1);

return [

    'exports' => [
        'max_rows' => (int) env(
            'REPORT_EXPORT_MAX_ROWS',
            10_000,
        ),
    ],

    'operations' => [
        'pending_age_hours' => 24,

        'processing_age_hours' => 48,

        'unfulfilled_age_hours' => 48,
    ],

    'cod_operations' => [
        'unpaid_stale_hours' => 48,

        'due_grace_hours' => 24,
    ],
];
