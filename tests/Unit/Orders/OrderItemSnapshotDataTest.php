<?php

declare(strict_types=1);

namespace Tests\Unit\Orders;

use App\Domain\Orders\Data\OrderItemSnapshotData;
use PHPUnit\Framework\TestCase;

final class OrderItemSnapshotDataTest extends TestCase
{
    public function test_it_exposes_order_item_snapshot_contract(): void
    {
        $snapshot = new OrderItemSnapshotData(
            productId: 10,
            variantId: 20,
            productName: 'Test Product',
            sku: 'SKU-001',
            variantDescription: 'Black / Large',
            unitPrice: 15000,
            quantity: 2,
            stockWasDecremented: true,
            lineTotal: 30000,
        );

        $this->assertSame(
            [
                'product_id' => 10,
                'variant_id' => 20,
                'product_name' => 'Test Product',
                'sku' => 'SKU-001',
                'variant_description' => 'Black / Large',
                'unit_price' => 15000,
                'quantity' => 2,
                'stock_was_decremented' => true,
                'line_total' => 30000,
            ],
            $snapshot->toArray(),
        );
    }
}
