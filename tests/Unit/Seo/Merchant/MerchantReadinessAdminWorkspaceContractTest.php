<?php

declare(strict_types=1);

namespace Tests\Unit\Seo\Merchant;

use PHPUnit\Framework\TestCase;

final class MerchantReadinessAdminWorkspaceContractTest extends TestCase
{
    public function test_workspace_ui_keeps_readiness_navigation_safe_while_allowing_explicit_export_operations(): void
    {
        $path =
            dirname(
                __DIR__,
                4,
            )
            .'/resources/js/Pages/Admin/Seo/Merchant/Readiness.tsx';

        $source =
            file_get_contents(
                $path,
            );

        if ($source === false) {
            self::fail(
                'Unable to read the Merchant readiness TSX source.',
            );
        }

        self::assertStringContainsString(
            'router.get(',
            $source,
        );

        self::assertStringContainsString(
            'exportForm.post(',
            $source,
        );

        self::assertStringContainsString(
            "'admin.seo.merchant.exports.store'",
            $source,
        );

        self::assertStringContainsString(
            'router.post(',
            $source,
        );

        self::assertStringContainsString(
            "'admin.seo.merchant.exports.retry'",
            $source,
        );

        self::assertStringNotContainsString(
            'router.put(',
            $source,
        );

        self::assertStringNotContainsString(
            'router.delete(',
            $source,
        );

        self::assertStringNotContainsString(
            'Submit to Google',
            $source,
        );

        self::assertStringContainsString(
            'Nothing on this page submits data to Google.',
            $source,
        );
    }
}
