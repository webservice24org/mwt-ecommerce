<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use PHPUnit\Framework\TestCase;

final class TechnicalSeoServerAuthorityContractTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private function storefrontControllers(): array
    {
        return [
            'product' => 'app/Http/Controllers/Frontend/ProductController.php',
            'category' => 'app/Http/Controllers/Frontend/CategoryController.php',
            'brand' => 'app/Http/Controllers/Frontend/BrandController.php',
            'page' => 'app/Http/Controllers/Frontend/PageController.php',
        ];
    }

    public function test_primary_storefront_entities_resolve_seo_and_canonical_on_the_server(): void
    {
        foreach ($this->storefrontControllers() as $entity => $relativePath) {
            $source = $this->source($relativePath);

            self::assertStringContainsString(
                'StorefrontSeoResolver',
                $source,
                sprintf('%s storefront controller must use the server SEO resolver.', $entity),
            );

            self::assertStringContainsString(
                'CanonicalUrlResolver',
                $source,
                sprintf('%s storefront controller must resolve canonical URLs on the server.', $entity),
            );

            self::assertStringContainsString(
                "'seo'",
                $source,
                sprintf('%s storefront controller must expose resolved SEO to the Inertia response.', $entity),
            );
        }
    }

    public function test_filterable_storefront_routes_keep_server_side_indexation_policy(): void
    {
        foreach ([
            'app/Http/Controllers/Frontend/ProductController.php',
            'app/Http/Controllers/Frontend/CategoryController.php',
            'app/Http/Controllers/Frontend/BrandController.php',
        ] as $relativePath) {
            $source = $this->source($relativePath);

            self::assertStringContainsString(
                'StorefrontIndexationPolicy',
                $source,
                sprintf('%s must keep indexation decisions server-side.', $relativePath),
            );
        }
    }

    public function test_technical_seo_does_not_depend_on_browser_only_execution(): void
    {
        foreach ($this->storefrontControllers() as $relativePath) {
            $source = $this->source($relativePath);

            self::assertStringNotContainsString('window.location', $source);
            self::assertStringNotContainsString('document.location', $source);
            self::assertStringNotContainsString('localStorage', $source);
        }
    }

    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 3).'/'.$relativePath;

        self::assertFileExists($path);

        $source = file_get_contents($path);

        self::assertIsString($source);

        return $source;
    }
}
