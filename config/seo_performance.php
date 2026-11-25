<?php

declare(strict_types=1);

return [
    'search_console' => [
        /*
         * Absolute or project-relative path to a Google service-account JSON file.
         * Never place this file below public/ and never commit it to source control.
         */
        'service_account_json' => env('SEO_SEARCH_CONSOLE_SERVICE_ACCOUNT_JSON'),
    ],
];
