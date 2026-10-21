<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ProductInventorySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_have_inventory_columns(): void
    {
        $this->assertTrue(
            Schema::hasColumns(
                'products',
                [
                    'track_inventory',
                    'stock_quantity',
                ],
            ),
        );
    }

    public function test_product_variants_have_stock_quantity(): void
    {
        $this->assertTrue(
            Schema::hasColumn(
                'product_variants',
                'stock_quantity',
            ),
        );
    }
}
