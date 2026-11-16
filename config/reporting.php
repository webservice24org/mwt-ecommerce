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

    'inventory_operations' => [
        'low_stock_threshold' => 5,

        'critical_stock_threshold' => 2,
    ],
    'action_queue' => [
        /*
         * Orders/COD need a date range because their existing
         * authoritative queries are date-range based.
         */
        'lookback_days' => 90,

        'provider_page_size' => 100,

        'provider_max_rows' => 10_000,
    ],
    'operational_exports' => [
        'max_rows' => 10_000,

        'utf8_bom' => true,
    ],
];
