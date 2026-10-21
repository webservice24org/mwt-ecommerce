<?php

declare(strict_types=1);

namespace Tests\Unit\Orders;

use App\Domain\Orders\Data\OrderItemSnapshotData;
use PHPUnit\Framework\TestCase;

final class OrderItemSnapshotDataTest extends TestCase
{
    public function test_it_exposes_order_item_snapshot_contract(): void
    {
        $snapshot =
            new OrderItemSnapshotData(
                productId: 10,
                variantId: 20,
                productName: 'Example Product',
                sku: 'VAR-001',
                variantDescription: 'Large / Black',
                unitPrice: 12500,
                quantity: 2,
                lineTotal: 25000,
            );

        $this->assertSame(
            [
                'product_id' => 10,
                'variant_id' => 20,
                'product_name' => 'Example Product',
                'sku' => 'VAR-001',
                'variant_description' => 'Large / Black',
                'unit_price' => 12500,
                'quantity' => 2,
                'line_total' => 25000,
            ],
            $snapshot->toArray(),
        );
    }
}
