<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class TechnicalSeoServerAuthorityPerformanceContractTest extends TestCase
{
    #[DataProvider('storefrontControllerProvider')]
    public function test_primary_storefront_routes_receive_server_authoritative_seo(
        string $relativePath,
        string $component,
    ): void {
        $source = $this->source($relativePath);

        self::assertStringContainsString(
            'StorefrontSeoResolver',
            $source,
            $relativePath.' must resolve SEO on the Laravel side.',
        );

        self::assertStringContainsString(
            "'seo' =>",
            $source,
            $relativePath.' must send resolved SEO in the Inertia payload.',
        );

        self::assertStringContainsString(
            $component,
            $source,
        );
    }

    public function test_product_route_builds_structured_data_before_rendering_react(): void
    {
        $source = $this->source(
            'app/Http/Controllers/Frontend/ProductController.php',
        );

        self::assertStringContainsString(
            'ProductStructuredDataResolver',
            $source,
        );

        self::assertStringContainsString(
            'StructuredDataDocumentMerger',
            $source,
        );

        self::assertStringContainsString(
            'ProductVariantStructuredDataResolver',
            $source,
        );

        self::assertStringContainsString(
            'StorefrontProductReviewAggregateQuery',
            $source,
        );

        self::assertStringContainsString(
            'AggregateRatingStructuredDataEnricher',
            $source,
        );

        self::assertStringContainsString(
            'SeoSlugRedirectHistory',
            $source,
        );

        self::assertStringContainsString(
            "'structuredData' =>",
            $source,
        );
    }

    public function test_builder_page_route_builds_breadcrumb_json_ld_before_rendering_react(): void
    {
        $source = $this->source(
            'app/Http/Controllers/Frontend/PageController.php',
        );

        self::assertStringContainsString(
            'BreadcrumbStructuredDataResolver',
            $source,
        );

        self::assertStringContainsString(
            "'breadcrumbStructuredData' =>",
            $source,
        );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function storefrontControllerProvider(): array
    {
        return [
            'product' => [
                'app/Http/Controllers/Frontend/ProductController.php',
                "'Frontend/Products/Show'",
            ],
            'category' => [
                'app/Http/Controllers/Frontend/CategoryController.php',
                "'Frontend/Categories/Show'",
            ],
            'brand' => [
                'app/Http/Controllers/Frontend/BrandController.php',
                "'Frontend/Brands/Show'",
            ],
            'builder page' => [
                'app/Http/Controllers/Frontend/PageController.php',
                "'Frontend/Pages/Show'",
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
