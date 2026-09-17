<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Actions\CreateProductVariantAction;
use App\Domain\Catalog\Actions\UpdateProductAction;
use App\Domain\Catalog\Actions\UpdateProductVariantAction;
use App\Domain\Catalog\Data\CreateProductVariantData;
use App\Domain\Catalog\Data\UpdateProductData;
use App\Domain\Catalog\Data\UpdateProductVariantData;
use App\Domain\Catalog\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ProductEffectiveCommercialActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_variant_rejects_invalid_effective_inherited_pricing(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => 20000,
                'compare_at_price' => 25000,
            ]);

        $data = new CreateProductVariantData(
            sku: 'INVALID-CREATE-001',
            name: 'Invalid Variant',
            price: 30000,
            compareAtPrice: null,
            costPrice: null,
            barcode: null,
            position: 0,
            isActive: true,
            isDefault: true,
            weight: null,
            attributeValueIds: [],
        );

        try {
            app(CreateProductVariantAction::class)->execute(
                product: $product,
                data: $data,
            );

            $this->fail(
                'Creating a variant with invalid effective pricing should fail.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'compare_at_price',
                $exception->errors(),
            );
        }

        $this->assertDatabaseMissing('product_variants', [
            'product_id' => $product->id,
            'sku' => 'INVALID-CREATE-001',
        ]);
    }

    public function test_update_variant_rejects_invalid_effective_pricing_and_preserves_original_values(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => 20000,
                'compare_at_price' => 25000,
            ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'VARIANT-UPDATE-001',
            'name' => 'Original Variant',
            'price' => null,
            'compare_at_price' => null,
            'cost_price' => null,
            'is_active' => true,
            'is_default' => true,
        ]);

        $data = new UpdateProductVariantData(
            sku: 'VARIANT-UPDATE-001',
            name: 'Should Not Persist',
            price: 30000,
            compareAtPrice: null,
            costPrice: null,
            barcode: null,
            position: 0,
            isActive: true,
            isDefault: true,
            weight: null,
            attributeValueIds: [],
        );

        try {
            app(UpdateProductVariantAction::class)->execute(
                variant: $variant,
                data: $data,
            );

            $this->fail(
                'Updating a variant with invalid effective pricing should fail.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'compare_at_price',
                $exception->errors(),
            );
        }

        $variant->refresh();

        $this->assertSame(
            'Original Variant',
            $variant->name,
        );

        $this->assertNull($variant->price);
        $this->assertNull($variant->compare_at_price);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'sku' => 'VARIANT-UPDATE-001',
            'name' => 'Original Variant',
            'price' => null,
            'compare_at_price' => null,
        ]);
    }

    public function test_update_product_rejects_pricing_that_would_invalidate_existing_variant_and_preserves_product(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'name' => 'Original Product',
                'price' => 20000,
                'compare_at_price' => 30000,
            ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'INHERITING-VARIANT-001',
            'price' => null,
            'compare_at_price' => 25000,
            'is_active' => true,
            'is_default' => true,
        ]);

        $data = new UpdateProductData(
            brandId: $product->brand_id,
            type: ProductType::Variable,
            sku: $product->sku,
            price: 28000,
            compareAtPrice: 30000,
            costPrice: $product->cost_price,
            name: 'Should Not Persist',
            slug: $product->slug,
            shortDescription: $product->short_description,
            description: $product->description,
            status: $product->status,
            isFeatured: $product->is_featured,
            position: $product->position,
            publishedAt: $product->published_at,
            metaTitle: $product->meta_title,
            metaDescription: $product->meta_description,
            categoryIds: [],
        );

        try {
            app(UpdateProductAction::class)->execute(
                product: $product,
                data: $data,
            );

            $this->fail(
                'Product pricing that invalidates an existing variant should fail.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'compare_at_price',
                $exception->errors(),
            );
        }

        $product->refresh();

        $this->assertSame(
            'Original Product',
            $product->name,
        );

        $this->assertSame(
            20000,
            $product->price,
        );

        $this->assertSame(
            30000,
            $product->compare_at_price,
        );

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Original Product',
            'price' => 20000,
            'compare_at_price' => 30000,
        ]);
    }

    public function test_valid_product_pricing_update_persists_when_all_effective_variant_prices_remain_valid(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'name' => 'Original Product',
                'price' => 20000,
                'compare_at_price' => 30000,
            ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'VALID-VARIANT-001',
            'price' => null,
            'compare_at_price' => 28000,
            'is_active' => true,
            'is_default' => true,
        ]);

        $data = new UpdateProductData(
            brandId: $product->brand_id,
            type: ProductType::Variable,
            sku: $product->sku,
            price: 21000,
            compareAtPrice: 30000,
            costPrice: $product->cost_price,
            name: 'Updated Product',
            slug: $product->slug,
            shortDescription: $product->short_description,
            description: $product->description,
            status: $product->status,
            isFeatured: $product->is_featured,
            position: $product->position,
            publishedAt: $product->published_at,
            metaTitle: $product->meta_title,
            metaDescription: $product->meta_description,
            categoryIds: [],
        );

        $updated = app(UpdateProductAction::class)->execute(
            product: $product,
            data: $data,
        );

        $this->assertSame(
            'Updated Product',
            $updated->name,
        );

        $this->assertSame(
            21000,
            $updated->price,
        );

        $this->assertSame(
            30000,
            $updated->compare_at_price,
        );

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product',
            'price' => 21000,
            'compare_at_price' => 30000,
        ]);
    }
}
