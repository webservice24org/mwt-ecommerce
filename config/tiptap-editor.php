<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Uploads
    |--------------------------------------------------------------------------
    */

    'image_upload' => [
        'enabled' => env(
            'TIPTAP_EDITOR_UPLOAD_ENABLED',
            true
        ),

        'disk' => env(
            'TIPTAP_EDITOR_DISK',
            'public'
        ),

        'temporary_directory' => env(
            'TIPTAP_EDITOR_TEMP_DIRECTORY',
            'editor/tmp'
        ),

        'permanent_directory' => env(
            'TIPTAP_EDITOR_IMAGE_DIRECTORY',
            'editor/images'
        ),

        'temporary_lifetime' => (int) env(
            'TIPTAP_EDITOR_TEMP_LIFETIME',
            24
        ),

        /*
         * Kept false deliberately. The package never deletes permanent
         * media merely because it is no longer referenced by editor HTML.
         */
        'delete_unreferenced_on_save' => false,

        'max_size' => (int) env(
            'TIPTAP_EDITOR_MAX_IMAGE_SIZE',
            5120
        ),

        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Temporary Media Cleanup
    |--------------------------------------------------------------------------
    |
    | When enabled, the package registers its cleanup command with Laravel's
    | scheduler once per hour. The host application must still run Laravel's
    | scheduler (for example with `php artisan schedule:work` locally or the
    | normal production cron entry).
    |
    */

    'cleanup' => [
        'schedule' => env(
            'TIPTAP_EDITOR_CLEANUP_SCHEDULE',
            true
        ),
    ],
];
