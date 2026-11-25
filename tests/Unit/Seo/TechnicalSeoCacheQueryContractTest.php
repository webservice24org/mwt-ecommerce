<?php

declare(strict_types=1);

namespace Tests\Unit\Seo;

use Tests\TestCase;

final class TechnicalSeoCacheQueryContractTest extends TestCase
{
    public function test_product_catalog_writes_keep_storefront_cache_invalidation_contract(): void
    {
        $create = $this->source(
            'app/Domain/Catalog/Actions/CreateProductAction.php',
        );

        $update = $this->source(
            'app/Domain/Catalog/Actions/UpdateProductAction.php',
        );

        $delete = $this->source(
            'app/Domain/Catalog/Actions/DeleteProductAction.php',
        );

        foreach (
            [
                'CreateProductAction.php' => $create,
                'UpdateProductAction.php' => $update,
                'DeleteProductAction.php' => $delete,
            ] as $name => $source
        ) {
            self::assertStringContainsString(
                'StorefrontCatalogCache',
                $source,
                $name.' must keep the shared storefront catalog cache dependency.',
            );

            self::assertStringContainsString(
                '->invalidate()',
                $source,
                $name.' must invalidate cached storefront catalog reads after mutation.',
            );
        }
    }

    public function test_seo_settings_writes_forget_cached_settings_before_next_read(): void
    {
        $service = $this->source(
            'app/Domain/Seo/Services/SeoSettingsService.php',
        );

        self::assertStringContainsString(
            'SeoSettingsCache',
            $service,
        );

        self::assertStringContainsString(
            'public function save(',
            $service,
        );

        self::assertStringContainsString(
            '->forget()',
            $service,
            'SEO settings mutation must invalidate the cached authoritative settings snapshot.',
        );
    }

    public function test_product_detail_query_keeps_cached_dto_and_authoritative_model_paths_separate_and_bounded(): void
    {
        $query = $this->source(
            'app/Domain/Catalog/Queries/Storefront/StorefrontProductDetailQuery.php',
        );

        self::assertStringContainsString(
            'rememberNotNull(',
            $query,
        );

        self::assertStringContainsString(
            'findBySlugOrFail(',
            $query,
        );

        self::assertStringContainsString(
            "'seoMeta'",
            $query,
            'The authoritative storefront product lookup must eager-load SEO metadata instead of causing a second SEO product lookup.',
        );

        self::assertStringNotContainsString(
            '->get()',
            $this->methodSlice(
                $query,
                'private function query()',
            ),
            'The detail query contract must stay single-product/bounded rather than materializing an unbounded collection.',
        );
    }

    private function source(string $relativePath): string
    {
        $path = base_path($relativePath);

        $source = file_get_contents($path);

        self::assertNotFalse(
            $source,
            'Unable to read source contract: '.$relativePath,
        );

        return $source;
    }

    private function methodSlice(
        string $source,
        string $needle,
    ): string {
        $offset = strpos(
            $source,
            $needle,
        );

        self::assertNotFalse(
            $offset,
            'Expected source contract was not found: '.$needle,
        );

        return substr(
            $source,
            (int) $offset,
        );
    }
}
