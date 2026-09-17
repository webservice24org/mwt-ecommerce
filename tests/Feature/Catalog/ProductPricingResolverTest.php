<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Services\ProductPricingResolver;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

final class ProductPricingResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_pricing_resolves_product_values_without_variant(): void
    {
        $product = Product::factory()->create([
            'price' => 20000,
            'compare_at_price' => 25000,
            'cost_price' => 12000,
        ]);

        $pricing = app(ProductPricingResolver::class)
            ->resolve($product);

        $this->assertSame(20000, $pricing->price);
        $this->assertSame(25000, $pricing->compareAtPrice);
        $this->assertSame(12000, $pricing->costPrice);

        $this->assertFalse($pricing->priceInherited);
        $this->assertFalse($pricing->compareAtPriceInherited);
        $this->assertFalse($pricing->costPriceInherited);

        $this->assertTrue($pricing->hasPrice());
        $this->assertTrue($pricing->isOnSale());
    }

    public function test_variant_null_values_inherit_product_values(): void
    {
        $product = Product::factory()->create([
            'price' => 20000,
            'compare_at_price' => 25000,
            'cost_price' => 12000,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => null,
            'compare_at_price' => null,
            'cost_price' => null,
        ]);

        $pricing = app(ProductPricingResolver::class)
            ->resolve(
                product: $product,
                variant: $variant,
            );

        $this->assertSame(20000, $pricing->price);
        $this->assertSame(25000, $pricing->compareAtPrice);
        $this->assertSame(12000, $pricing->costPrice);

        $this->assertTrue($pricing->priceInherited);
        $this->assertTrue($pricing->compareAtPriceInherited);
        $this->assertTrue($pricing->costPriceInherited);
    }

    public function test_variant_values_override_product_values_independently(): void
    {
        $product = Product::factory()->create([
            'price' => 20000,
            'compare_at_price' => 25000,
            'cost_price' => 12000,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 21000,
            'compare_at_price' => null,
            'cost_price' => 13000,
        ]);

        $pricing = app(ProductPricingResolver::class)
            ->resolve(
                product: $product,
                variant: $variant,
            );

        $this->assertSame(21000, $pricing->price);
        $this->assertSame(25000, $pricing->compareAtPrice);
        $this->assertSame(13000, $pricing->costPrice);

        $this->assertFalse($pricing->priceInherited);
        $this->assertTrue($pricing->compareAtPriceInherited);
        $this->assertFalse($pricing->costPriceInherited);
    }

    public function test_zero_variant_price_is_an_explicit_override(): void
    {
        $product = Product::factory()->create([
            'price' => 20000,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 0,
        ]);

        $pricing = app(ProductPricingResolver::class)
            ->resolve(
                product: $product,
                variant: $variant,
            );

        $this->assertSame(0, $pricing->price);
        $this->assertFalse($pricing->priceInherited);
        $this->assertTrue($pricing->hasPrice());
    }

    public function test_missing_product_price_remains_null_when_variant_also_has_no_price(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => null,
            ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => null,
        ]);

        $pricing = app(ProductPricingResolver::class)
            ->resolve(
                product: $product,
                variant: $variant,
            );

        $this->assertNull($pricing->price);
        $this->assertTrue($pricing->priceInherited);
        $this->assertFalse($pricing->hasPrice());
    }

    public function test_equal_compare_at_price_is_not_considered_on_sale(): void
    {
        $product = Product::factory()->create([
            'price' => 20000,
            'compare_at_price' => 20000,
        ]);

        $pricing = app(ProductPricingResolver::class)
            ->resolve($product);

        $this->assertFalse($pricing->isOnSale());
    }

    public function test_variant_from_another_product_cannot_be_resolved(): void
    {
        $product = Product::factory()->create();

        $otherProduct = Product::factory()->create();

        $variant = ProductVariant::factory()->create([
            'product_id' => $otherProduct->id,
        ]);

        $this->expectException(
            InvalidArgumentException::class,
        );

        app(ProductPricingResolver::class)
            ->resolve(
                product: $product,
                variant: $variant,
            );
    }
}
