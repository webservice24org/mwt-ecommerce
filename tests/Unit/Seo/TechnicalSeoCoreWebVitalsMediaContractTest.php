<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use Tests\TestCase;

final class TechnicalSeoCoreWebVitalsMediaContractTest extends TestCase
{
    public function test_primary_product_gallery_media_reserves_intrinsic_space_and_prioritizes_lcp_candidate(): void
    {
        $source = $this->source(
            'resources/js/Components/Frontend/Catalog/ProductDetails/ProductGalleryViewer.tsx',
        );

        self::assertStringContainsString(
            'width={image.width ?? undefined}',
            $source,
        );

        self::assertStringContainsString(
            'height={image.height ?? undefined}',
            $source,
        );

        self::assertStringContainsString(
            'loading="eager"',
            $source,
        );

        self::assertStringContainsString(
            'fetchPriority="high"',
            $source,
        );

        self::assertStringContainsString(
            'decoding="async"',
            $source,
        );

        self::assertStringContainsString(
            'className="h-full w-full object-contain"',
            $source,
        );
    }

    public function test_shared_product_images_keep_intrinsic_dimensions_and_priority_controls(): void
    {
        $source = $this->source(
            'resources/js/Components/Frontend/Catalog/ProductImage.tsx',
        );

        self::assertStringContainsString(
            'width={image.width ?? undefined}',
            $source,
        );

        self::assertStringContainsString(
            'height={image.height ?? undefined}',
            $source,
        );

        self::assertStringContainsString(
            "loading={priority ? 'eager' : 'lazy'}",
            $source,
        );

        self::assertMatchesRegularExpression(
            "/fetchPriority=\{\s*priority\s*\?\s*'high'\s*:\s*'auto'\s*\}/s",
            $source,
        );
    }

    private function source(string $relativePath): string
    {
        $path = base_path($relativePath);

        self::assertFileExists($path);

        $source = file_get_contents($path);

        self::assertIsString($source);

        return $source;
    }
}
