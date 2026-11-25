<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use PHPUnit\Framework\TestCase;

final class ProductPreferredSocialImageSourceContractTest extends TestCase
{
    public function test_product_page_passes_only_the_first_authoritative_storefront_image_to_social_metadata(): void
    {
        $path =
            dirname(
                __DIR__,
                3,
            )
            .'/resources/js/Pages/Frontend/Products/Show.tsx';

        self::assertFileExists(
            $path,
        );

        $source =
            file_get_contents(
                $path,
            );

        self::assertIsString(
            $source,
        );

        self::assertStringContainsString(
            'const preferredSocialImage',
            $source,
        );

        self::assertStringContainsString(
            'product.images[0]',
            $source,
        );

        self::assertStringContainsString(
            'imageMetadata={',
            $source,
        );

        self::assertStringContainsString(
            'preferredSocialImage',
            $source,
        );

        self::assertStringContainsString(
            'jsonLd={',
            $source,
        );

        self::assertStringContainsString(
            'structuredData',
            $source,
        );
    }
}
