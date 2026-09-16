<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Domain\Catalog\Services\ProductSeoService;
use App\Models\Product;
use PHPUnit\Framework\TestCase;

final class ProductSeoServiceTest extends TestCase
{
    private ProductSeoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProductSeoService;
    }

    public function test_explicit_meta_title_is_used(): void
    {
        $product = new Product([
            'name' => 'Product Name',
            'meta_title' => 'Custom SEO Title',
        ]);

        $this->assertSame(
            'Custom SEO Title',
            $this->service->title($product),
        );
    }

    public function test_product_name_is_title_fallback(): void
    {
        $product = new Product([
            'name' => 'Product Name',
            'meta_title' => null,
        ]);

        $this->assertSame(
            'Product Name',
            $this->service->title($product),
        );
    }

    public function test_meta_description_has_highest_priority(): void
    {
        $product = new Product([
            'meta_description' => 'SEO description',
            'short_description' => 'Short description',
            'description' => 'Full description',
        ]);

        $this->assertSame(
            'SEO description',
            $this->service->description($product),
        );
    }

    public function test_short_description_is_used_as_fallback(): void
    {
        $product = new Product([
            'meta_description' => null,
            'short_description' => '<p>Short product description</p>',
            'description' => '<p>Full description</p>',
        ]);

        $this->assertSame(
            'Short product description',
            $this->service->description($product),
        );
    }

    public function test_full_description_is_final_fallback(): void
    {
        $product = new Product([
            'meta_description' => null,
            'short_description' => null,
            'description' => '<p>Full <strong>product</strong> description</p>',
        ]);

        $this->assertSame(
            'Full product description',
            $this->service->description($product),
        );
    }

    public function test_description_returns_null_when_no_content_exists(): void
    {
        $product = new Product([
            'meta_description' => null,
            'short_description' => null,
            'description' => null,
        ]);

        $this->assertNull(
            $this->service->description($product),
        );
    }
}
