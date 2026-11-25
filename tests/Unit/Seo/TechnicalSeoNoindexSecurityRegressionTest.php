<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use App\Domain\Seo\Services\StorefrontIndexationPolicy;
use Tests\TestCase;

final class TechnicalSeoNoindexSecurityRegressionTest extends TestCase
{
    public function test_internal_search_and_sort_variants_fail_closed_for_indexation(): void
    {
        $policy = app(StorefrontIndexationPolicy::class);

        self::assertTrue(
            $policy->allowsListingIndexing([]),
            'A clean listing may remain indexable.',
        );

        foreach ([
            ['q' => 'usb cable'],
            ['sort' => 'price_asc'],
            ['q' => 'usb cable', 'sort' => 'price_desc'],
        ] as $query) {
            self::assertFalse(
                $policy->allowsListingIndexing($query),
                'Search/sort listing variants must fail closed for indexing.',
            );
        }
    }

    public function test_filterable_storefront_controllers_keep_global_noindex_as_an_upper_bound(): void
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
                $relativePath.' must keep server-side listing indexation policy.',
            );

            self::assertTrue(
                str_contains($source, 'defaultRobotsIndex')
                || str_contains($source, '->robotsIndex'),
                $relativePath.' must preserve an authoritative robots-index upper bound before route-policy restriction.',
            );

            self::assertStringContainsString(
                'allowsListingIndexing',
                $source,
                $relativePath.' must restrict listing variants through the policy.',
            );

            self::assertStringNotContainsString(
                '$robotsIndex = true',
                $source,
                $relativePath.' must not hard-code an indexable listing.',
            );
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
