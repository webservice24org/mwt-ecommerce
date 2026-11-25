<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class TechnicalSeoHeadPerformanceContractTest extends TestCase
{
    public function test_storefront_head_consumes_authoritative_payload_without_client_fetching(): void
    {
        $source = $this->source(
            'resources/js/Components/Frontend/Seo/StorefrontSeo.tsx',
        );

        self::assertStringContainsString(
            'Head',
            $source,
        );

        self::assertStringContainsString(
            'props.seo',
            $source,
        );

        self::assertStringContainsString(
            'rel="canonical"',
            $source,
        );

        self::assertStringContainsString(
            'name="robots"',
            $source,
        );

        self::assertStringContainsString(
            'type="application/ld+json"',
            $source,
        );

        self::assertStringContainsString(
            'JSON.stringify(',
            $source,
        );

        self::assertStringNotContainsString(
            'fetch(',
            $source,
            'SEO head rendering must not wait for a browser-side fetch.',
        );

        self::assertStringNotContainsString(
            'axios.',
            $source,
            'SEO head rendering must not wait for a browser-side axios request.',
        );

        self::assertStringNotContainsString(
            'useEffect(',
            $source,
            'SEO head rendering must not depend on a post-render effect.',
        );
    }

    #[DataProvider('storefrontPageProvider')]
    public function test_primary_storefront_pages_render_storefront_seo_from_inertia_props(
        string $relativePath,
    ): void {
        $source = $this->source($relativePath);

        self::assertStringContainsString(
            'StorefrontSeo',
            $source,
        );

        self::assertStringContainsString(
            'seo',
            $source,
        );

        self::assertStringNotContainsString(
            'fetchSeo',
            $source,
        );
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function storefrontPageProvider(): array
    {
        return [
            'product' => [
                'resources/js/Pages/Frontend/Products/Show.tsx',
            ],
            'category' => [
                'resources/js/Pages/Frontend/Categories/Show.tsx',
            ],
            'brand' => [
                'resources/js/Pages/Frontend/Brands/Show.tsx',
            ],
            'builder page' => [
                'resources/js/Pages/Frontend/Pages/Show.tsx',
            ],
        ];
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
