<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Merchant;

use PHPUnit\Framework\TestCase;

final class MerchantFeedExportSourceContractTest extends TestCase
{
    public function test_m4c_domain_does_not_create_queue_http_or_download_surface(): void
    {
        $root =
            dirname(
                __DIR__,
                4,
            );

        $lifecycle =
            file_get_contents(
                $root
                .'/app/Domain/Seo/Merchant/Services/MerchantFeedExportLifecycle.php',
            );

        self::assertIsString(
            $lifecycle,
        );

        self::assertStringNotContainsString(
            'dispatch(',
            $lifecycle,
        );

        self::assertStringNotContainsString(
            'Storage::',
            $lifecycle,
        );

        self::assertStringNotContainsString(
            'response()->download',
            $lifecycle,
        );
    }
}
