<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Merchant;

use Tests\TestCase;

final class MerchantFeedExportOperationsContractTest extends TestCase
{
    public function test_download_service_verifies_integrity_and_never_generates_public_storage_urls(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Domain/Seo/Merchant/Services/MerchantFeedExportDownload.php',
                ),
            );

        self::assertIsString(
            $source,
        );

        self::assertStringContainsString(
            'readStream',
            $source,
        );

        self::assertStringContainsString(
            'hash_equals',
            $source,
        );

        self::assertStringContainsString(
            'no-store',
            $source,
        );

        self::assertStringNotContainsString(
            'Storage::url',
            $source,
        );

        self::assertStringNotContainsString(
            'temporaryUrl',
            $source,
        );
    }

    public function test_workspace_contract_does_not_send_storage_disk_or_path_to_react(): void
    {
        $source =
            file_get_contents(
                app_path(
                    'Domain/Seo/Merchant/Queries/GetMerchantFeedExportHistoryQuery.php',
                ),
            );

        self::assertIsString(
            $source,
        );

        self::assertStringNotContainsString(
            "'file_path' =>",
            $source,
        );

        self::assertStringNotContainsString(
            "'file_disk' =>",
            $source,
        );
    }

    public function test_operations_ui_keeps_google_submission_and_public_feed_out_of_scope(): void
    {
        $source =
            file_get_contents(
                resource_path(
                    'js/Pages/Admin/Seo/Merchant/Readiness.tsx',
                ),
            );

        self::assertIsString(
            $source,
        );

        self::assertStringContainsString(
            'Export TSV',
            $source,
        );

        self::assertStringContainsString(
            'exports.download',
            $source,
        );

        self::assertStringNotContainsString(
            'Merchant API',
            $source,
        );

        self::assertStringNotContainsString(
            'Submit to Google',
            $source,
        );
    }
}
