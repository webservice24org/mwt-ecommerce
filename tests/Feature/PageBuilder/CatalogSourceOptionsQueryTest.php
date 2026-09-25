<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\PageBuilder\Queries\GetCatalogCategoryOptionsQuery;
use App\Domain\PageBuilder\Queries\GetCatalogProductOptionsByIdsQuery;
use App\Domain\PageBuilder\Queries\SearchCatalogProductOptionsQuery;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CatalogSourceOptionsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_options_are_lightweight_and_sorted(): void
    {
        Category::factory()->create([
            'name' => 'Shoes',
            'slug' => 'shoes',
        ]);

        Category::factory()->create([
            'name' => 'Accessories',
            'slug' => 'accessories',
        ]);

        $options = app(
            GetCatalogCategoryOptionsQuery::class,
        )->handle();

        $this->assertCount(2, $options);

        $this->assertSame(
            'Accessories',
            $options[0]->name,
        );

        $this->assertSame(
            'Shoes',
            $options[1]->name,
        );

        $this->assertSame(
            [
                'id',
                'name',
                'slug',
            ],
            array_keys(
                $options[0]->toArray(),
            ),
        );
    }

    public function test_products_can_be_searched_by_name(): void
    {
        Product::factory()->create([
            'name' => 'Classic Shirt',
            'slug' => 'classic-shirt',
            'sku' => 'SHIRT-001',
        ]);

        Product::factory()->create([
            'name' => 'Leather Wallet',
            'slug' => 'leather-wallet',
            'sku' => 'WALLET-001',
        ]);

        $options = app(
            SearchCatalogProductOptionsQuery::class,
        )->handle('shirt');

        $this->assertCount(1, $options);

        $this->assertSame(
            'Classic Shirt',
            $options[0]->name,
        );
    }

    public function test_product_search_returns_no_results_for_empty_search(): void
    {
        Product::factory()
            ->count(3)
            ->create();

        $options = app(
            SearchCatalogProductOptionsQuery::class,
        )->handle('   ');

        $this->assertSame([], $options);
    }

    public function test_product_search_is_bounded(): void
    {
        Product::factory()
            ->count(25)
            ->create([
                'name' => 'Searchable Product',
            ]);

        $options = app(
            SearchCatalogProductOptionsQuery::class,
        )->handle(
            'Searchable',
            1000,
        );

        $this->assertCount(20, $options);
    }

    public function test_selected_products_are_resolved_in_requested_order(): void
    {
        $first = Product::factory()->create([
            'name' => 'First Product',
        ]);

        $second = Product::factory()->create([
            'name' => 'Second Product',
        ]);

        $third = Product::factory()->create([
            'name' => 'Third Product',
        ]);

        $options = app(
            GetCatalogProductOptionsByIdsQuery::class,
        )->handle([
            $third->id,
            $first->id,
            $second->id,
        ]);

        $this->assertSame(
            [
                $third->id,
                $first->id,
                $second->id,
            ],
            array_map(
                static fn ($option): int => $option->id,
                $options,
            ),
        );
    }

    public function test_missing_selected_products_are_ignored(): void
    {
        $product =
            Product::factory()->create();

        $options = app(
            GetCatalogProductOptionsByIdsQuery::class,
        )->handle([
            $product->id,
            999999,
        ]);

        $this->assertCount(1, $options);

        $this->assertSame(
            $product->id,
            $options[0]->id,
        );
    }
}
