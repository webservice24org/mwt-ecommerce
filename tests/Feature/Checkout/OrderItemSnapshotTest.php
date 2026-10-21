<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Domain\Cart\Data\CartItemData;
use App\Domain\Orders\Actions\BuildOrderItemSnapshotAction;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

final class OrderItemSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_simple_product_snapshot_uses_product_catalog_data(): void
    {
        $product = Product::factory()
            ->create([
                'name' => 'Original Product',
                'sku' => 'PRODUCT-001',
            ]);

        $item = new CartItemData(
            key: 'product:'.$product->id,

            productId: $product->id,

            variantId: null,

            quantity: 2,

            name: 'Browser Supplied Name',

            slug: $product->slug,

            variantName: null,

            imageUrl: null,

            unitPrice: 12500.0,

            lineTotal: 25000.0,
        );

        $snapshot = app(
            BuildOrderItemSnapshotAction::class,
        )->execute(
            $item,
        );

        $this->assertSame(
            $product->id,
            $snapshot->productId,
        );

        $this->assertNull(
            $snapshot->variantId,
        );

        /*
         * Product name comes from the database,
         * not from mutable/display cart data.
         */
        $this->assertSame(
            'Original Product',
            $snapshot->productName,
        );

        $this->assertSame(
            'PRODUCT-001',
            $snapshot->sku,
        );

        $this->assertNull(
            $snapshot->variantDescription,
        );

        $this->assertSame(
            12500,
            $snapshot->unitPrice,
        );

        $this->assertSame(
            2,
            $snapshot->quantity,
        );

        $this->assertSame(
            25000,
            $snapshot->lineTotal,
        );
    }

    public function test_variant_snapshot_prefers_variant_sku_and_name(): void
    {
        $product = Product::factory()
            ->create([
                'name' => 'Variable Product',
                'sku' => 'PRODUCT-BASE',
            ]);

        $variant = ProductVariant::factory()
            ->for($product)
            ->create([
                'sku' => 'VARIANT-001',
                'name' => 'Large / Black',
            ]);

        $item = new CartItemData(
            key: 'product:'
            .$product->id
            .':variant:'
            .$variant->id,

            productId: $product->id,

            variantId: $variant->id,

            quantity: 3,

            name: $product->name,

            slug: $product->slug,

            variantName: 'Mutable Browser Label',

            imageUrl: null,

            unitPrice: 10000.0,

            lineTotal: 30000.0,
        );

        $snapshot = app(
            BuildOrderItemSnapshotAction::class,
        )->execute(
            $item,
        );

        $this->assertSame(
            $variant->id,
            $snapshot->variantId,
        );

        $this->assertSame(
            'Variable Product',
            $snapshot->productName,
        );

        $this->assertSame(
            'VARIANT-001',
            $snapshot->sku,
        );

        $this->assertSame(
            'Large / Black',
            $snapshot->variantDescription,
        );

        $this->assertSame(
            10000,
            $snapshot->unitPrice,
        );

        $this->assertSame(
            30000,
            $snapshot->lineTotal,
        );
    }

    public function test_variant_must_belong_to_cart_product(): void
    {
        $product = Product::factory()
            ->create();

        $otherProduct =
            Product::factory()
                ->create();

        $otherVariant =
            ProductVariant::factory()
                ->for(
                    $otherProduct,
                )
                ->create();

        $item = new CartItemData(
            key: 'product:'
            .$product->id
            .':variant:'
            .$otherVariant->id,

            productId: $product->id,

            variantId: $otherVariant->id,

            quantity: 1,

            name: $product->name,

            slug: $product->slug,

            variantName: null,

            imageUrl: null,

            unitPrice: 10000.0,

            lineTotal: 10000.0,
        );

        $this->expectException(
            LogicException::class,
        );

        app(
            BuildOrderItemSnapshotAction::class,
        )->execute(
            $item,
        );
    }

    public function test_missing_product_cannot_be_snapshotted(): void
    {
        $item = new CartItemData(
            key: 'product:999999',

            productId: 999999,

            variantId: null,

            quantity: 1,

            name: 'Missing Product',

            slug: 'missing-product',

            variantName: null,

            imageUrl: null,

            unitPrice: 10000.0,

            lineTotal: 10000.0,
        );

        $this->expectException(
            LogicException::class,
        );

        app(
            BuildOrderItemSnapshotAction::class,
        )->execute(
            $item,
        );
    }
}
