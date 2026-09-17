<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Services\ProductCommercialIntegrityService;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ProductEffectiveCommercialIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_variant_can_inherit_valid_product_pricing(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => 20000,
                'compare_at_price' => 25000,
            ]);

        app(ProductCommercialIntegrityService::class)
            ->assertVariantEffectivePricingIsValid(
                product: $product,
                variantPrice: null,
                variantCompareAtPrice: null,
            );

        $this->assertTrue(true);
    }

    public function test_variant_price_override_cannot_exceed_inherited_compare_at_price(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => 20000,
                'compare_at_price' => 25000,
            ]);

        try {
            app(ProductCommercialIntegrityService::class)
                ->assertVariantEffectivePricingIsValid(
                    product: $product,
                    variantPrice: 30000,
                    variantCompareAtPrice: null,
                );

            $this->fail(
                'The effective variant pricing should have been rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertSame(
                'The effective compare-at price must be greater than or equal to the effective variant price.',
                $exception->errors()['compare_at_price'][0] ?? null,
            );
        }
    }

    public function test_variant_compare_at_override_cannot_be_lower_than_inherited_product_price(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => 20000,
                'compare_at_price' => 25000,
            ]);

        try {
            app(ProductCommercialIntegrityService::class)
                ->assertVariantEffectivePricingIsValid(
                    product: $product,
                    variantPrice: null,
                    variantCompareAtPrice: 15000,
                );

            $this->fail(
                'The effective variant pricing should have been rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'compare_at_price',
                $exception->errors(),
            );
        }
    }

    public function test_explicit_zero_variant_price_is_not_treated_as_inherited(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => 20000,
                'compare_at_price' => 25000,
            ]);

        app(ProductCommercialIntegrityService::class)
            ->assertVariantEffectivePricingIsValid(
                product: $product,
                variantPrice: 0,
                variantCompareAtPrice: null,
            );

        $this->assertTrue(true);
    }

    public function test_explicit_zero_compare_at_price_is_valid_when_effective_price_is_zero(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => 20000,
                'compare_at_price' => 25000,
            ]);

        app(ProductCommercialIntegrityService::class)
            ->assertVariantEffectivePricingIsValid(
                product: $product,
                variantPrice: 0,
                variantCompareAtPrice: 0,
            );

        $this->assertTrue(true);
    }

    public function test_product_price_update_cannot_invalidate_variant_compare_at_override(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => 20000,
                'compare_at_price' => 30000,
            ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => null,
            'compare_at_price' => 25000,
        ]);

        try {
            app(ProductCommercialIntegrityService::class)
                ->assertProductPricingIsValidForExistingVariants(
                    product: $product,
                    proposedPrice: 28000,
                    proposedCompareAtPrice: 30000,
                );

            $this->fail(
                'The product pricing update should have been rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'compare_at_price',
                $exception->errors(),
            );
        }
    }

    public function test_product_compare_at_update_cannot_invalidate_variant_price_override(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => 20000,
                'compare_at_price' => 35000,
            ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 30000,
            'compare_at_price' => null,
        ]);

        try {
            app(ProductCommercialIntegrityService::class)
                ->assertProductPricingIsValidForExistingVariants(
                    product: $product,
                    proposedPrice: 20000,
                    proposedCompareAtPrice: 25000,
                );

            $this->fail(
                'The product pricing update should have been rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'compare_at_price',
                $exception->errors(),
            );
        }
    }

    public function test_valid_product_pricing_update_remains_allowed_for_existing_variants(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => 20000,
                'compare_at_price' => 30000,
            ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => null,
            'compare_at_price' => 28000,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 22000,
            'compare_at_price' => null,
        ]);

        app(ProductCommercialIntegrityService::class)
            ->assertProductPricingIsValidForExistingVariants(
                product: $product,
                proposedPrice: 21000,
                proposedCompareAtPrice: 30000,
            );

        $this->assertTrue(true);
    }

    public function test_null_effective_price_does_not_create_false_validation_failure(): void
    {
        $product = Product::factory()
            ->variable()
            ->create([
                'price' => null,
                'compare_at_price' => 25000,
            ]);

        app(ProductCommercialIntegrityService::class)
            ->assertVariantEffectivePricingIsValid(
                product: $product,
                variantPrice: null,
                variantCompareAtPrice: null,
            );

        $this->assertTrue(true);
    }
}
