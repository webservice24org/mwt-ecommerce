<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Actions\CreateProductVariantAction;
use App\Domain\Catalog\Data\CreateProductVariantData;
use App\Domain\Catalog\Enums\ProductType;
use App\Domain\Catalog\Services\ProductCommercialIntegrityService;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ProductTypeVariantIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper for creating a basic variant DTO.
     */
    private function variantData(string $sku = 'TEST-VARIANT-001'): CreateProductVariantData
    {
        return new CreateProductVariantData(
            sku: $sku,
            name: 'Test Variant',
            price: null,
            compareAtPrice: null,
            costPrice: null,
            barcode: null,
            position: 0,
            isActive: true,
            isDefault: true,
            weight: null,
            attributeValueIds: [],
        );
    }

    public function test_simple_product_cannot_create_variant(): void
    {
        $product = Product::factory()->create([
            'type' => ProductType::Simple,
            'price' => 10000,
        ]);

        try {
            app(CreateProductVariantAction::class)->execute(
                product: $product,
                data: $this->variantData('SIMPLE-VARIANT-001'),
            );

            $this->fail('A simple product should not allow variants.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Variants can only be managed for variable products.',
                $exception->errors()['type'][0] ?? null,
            );
        }

        $this->assertDatabaseMissing('product_variants', [
            'product_id' => $product->id,
            'sku' => 'SIMPLE-VARIANT-001',
        ]);
    }

    public function test_variable_product_can_create_variant(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => 10000,
            ]);

        app(CreateProductVariantAction::class)->execute(
            product: $product,
            data: $this->variantData('VARIABLE-VARIANT-001'),
        );

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'VARIABLE-VARIANT-001',
        ]);
    }

    public function test_variable_product_with_variants_cannot_be_changed_to_simple(): void
    {
        $product = Product::factory()
            ->variable()
            ->create();

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_default' => true,
        ]);

        try {
            app(ProductCommercialIntegrityService::class)
                ->assertProductTypeTransitionIsValid(
                    product: $product,
                    requestedType: ProductType::Simple,
                );

            $this->fail(
                'Variable products with variants must not become simple.',
            );
        } catch (ValidationException $exception) {
            $this->assertSame(
                'A variable product with variants cannot be changed to a simple product. Delete its variants first.',
                $exception->errors()['type'][0] ?? null,
            );
        }

        $this->assertSame(
            ProductType::Variable,
            $product->refresh()->type,
        );
    }

    public function test_variable_product_without_variants_can_be_changed_to_simple(): void
    {
        $product = Product::factory()
            ->variable()
            ->create();

        app(ProductCommercialIntegrityService::class)
            ->assertProductTypeTransitionIsValid(
                product: $product,
                requestedType: ProductType::Simple,
            );

        $this->assertTrue(true);
    }

    public function test_simple_product_can_be_changed_to_variable(): void
    {
        $product = Product::factory()->create([
            'type' => ProductType::Simple,
        ]);

        app(ProductCommercialIntegrityService::class)
            ->assertProductTypeTransitionIsValid(
                product: $product,
                requestedType: ProductType::Variable,
            );

        $this->assertTrue(true);
    }

    public function test_variant_sku_cannot_match_existing_product_sku(): void
    {
        Product::factory()->create([
            'sku' => 'CATALOG-SKU-001',
        ]);

        $product = Product::factory()
            ->variable()
            ->create();

        try {
            app(CreateProductVariantAction::class)->execute(
                product: $product,
                data: $this->variantData('CATALOG-SKU-001'),
            );

            $this->fail(
                'Variant SKU should not duplicate a product SKU.',
            );
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The SKU has already been taken.',
                $exception->errors()['sku'][0] ?? null,
            );
        }
    }

    public function test_product_sku_cannot_match_existing_variant_sku(): void
    {
        $product = Product::factory()
            ->variable()
            ->create();

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'VARIANT-SKU-001',
        ]);

        try {
            app(ProductCommercialIntegrityService::class)
                ->assertSkuIsAvailable(
                    sku: 'VARIANT-SKU-001',
                );

            $this->fail(
                'Product SKU should not duplicate a variant SKU.',
            );
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The SKU has already been taken.',
                $exception->errors()['sku'][0] ?? null,
            );
        }
    }

    public function test_variant_can_keep_its_own_sku_when_updating(): void
    {
        $product = Product::factory()
            ->variable()
            ->create();

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'KEEP-MY-SKU',
        ]);

        app(ProductCommercialIntegrityService::class)
            ->assertSkuIsAvailable(
                sku: 'KEEP-MY-SKU',
                ignoreVariantId: $variant->id,
            );

        $this->assertTrue(true);
    }
}
