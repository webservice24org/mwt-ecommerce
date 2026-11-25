<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use PHPUnit\Framework\TestCase;

final class SeoSlugRedirectWiringContractTest extends TestCase
{
    public function test_catalog_slug_updates_record_history_and_storefronts_resolve_permanent_redirects(): void
    {
        foreach ([
            'app/Domain/Catalog/Actions/UpdateProductAction.php',
            'app/Domain/Catalog/Actions/UpdateCategoryAction.php',
            'app/Domain/Catalog/Actions/UpdateBrandAction.php',
        ] as $relativePath) {
            $source = $this->source($relativePath);

            self::assertStringContainsString(
                'SeoSlugRedirectHistory',
                $source,
            );
            self::assertStringContainsString(
                '->record(',
                $source,
            );
        }

        foreach ([
            'app/Http/Controllers/Frontend/ProductController.php',
            'app/Http/Controllers/Frontend/CategoryController.php',
            'app/Http/Controllers/Frontend/BrandController.php',
        ] as $relativePath) {
            $source = $this->source($relativePath);

            self::assertStringContainsString(
                'SeoSlugRedirectHistory',
                $source,
            );
            self::assertStringContainsString(
                'redirectResponse(',
                $source,
            );
            self::assertStringNotContainsString(
                'redirect()->away(',
                $source,
            );
        }

        $history = $this->source(
            'app/Domain/Seo/Redirects/Services/SeoSlugRedirectHistory.php',
        );

        self::assertStringContainsString('301', $history);
        self::assertStringContainsString('redirect()->to(', $history);
        self::assertStringNotContainsString('redirect()->away(', $history);
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
