<?php

declare(strict_types=1);

return [
    'exports' => [
        'max_rows' => (int) env(
            'REPORT_EXPORT_MAX_ROWS',
            10_000,
        ),
    ],
];
