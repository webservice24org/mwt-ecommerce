<?php

declare(strict_types=1);

return [
    'exports' => [
        'disk' => env(
            'MERCHANT_FEED_EXPORT_DISK',
            'local',
        ),

        'directory' => env(
            'MERCHANT_FEED_EXPORT_DIRECTORY',
            'merchant-feed-exports',
        ),

        'queue' => env(
            'MERCHANT_FEED_EXPORT_QUEUE',
            'default',
        ),
    ],
];
