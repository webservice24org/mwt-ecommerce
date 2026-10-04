<?php

declare(strict_types=1);

namespace Tests\Feature\PageBuilder;

use App\Domain\Catalog\Data\Storefront\StorefrontProductCardData;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\PageBuilder\Resolvers\ProductCollectionResolver;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductCollectionResolverTest extends TestCase
{
    use RefreshDatabase;

    private ProductCollectionResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(
            ProductCollectionResolver::class,
        );
    }

    public function test_latest_source_returns_published_products_in_latest_order(): void
    {
        $oldest = Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subDays(3),
        ]);

        $middle = Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subDays(2),
        ]);

        $latest = Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        Product::factory()->create([
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ]);

        $products = $this->resolver->resolve([
            'title' => 'Latest Products',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
        ]);

        $this->assertSame(
            [
                $latest->id,
                $middle->id,
                $oldest->id,
            ],
            $this->productIds($products),
        );
    }

    public function test_latest_source_excludes_future_published_products(): void
    {
        $published = Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->addDay(),
        ]);

        $products = $this->resolver->resolve([
            'title' => 'Latest Products',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
        ]);

        $this->assertSame(
            [
                $published->id,
            ],
            $this->productIds($products),
        );
    }

    public function test_latest_source_respects_limit(): void
    {
        Product::factory()
            ->count(5)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subDay(),
            ]);

        $products = $this->resolver->resolve([
            'title' => 'Latest Products',
            'limit' => 2,
            'source' => [
                'type' => 'latest',
            ],
        ]);

        $this->assertCount(
            2,
            $products,
        );
    }

    public function test_featured_source_returns_only_published_featured_products(): void
    {
        $featured = Product::factory()->create([
            'is_featured' => true,
            'status' => ProductStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        Product::factory()->create([
            'is_featured' => false,
            'status' => ProductStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        Product::factory()->create([
            'is_featured' => true,
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ]);

        $products = $this->resolver->resolve([
            'title' => 'Featured Products',
            'limit' => 8,
            'source' => [
                'type' => 'featured',
            ],
        ]);

        $this->assertSame(
            [
                $featured->id,
            ],
            $this->productIds($products),
        );
    }

    public function test_featured_source_respects_limit(): void
    {
        Product::factory()
            ->count(5)
            ->create([
                'is_featured' => true,
                'status' => ProductStatus::Published,
                'published_at' => now()->subDay(),
            ]);

        $products = $this->resolver->resolve([
            'title' => 'Featured Products',
            'limit' => 2,
            'source' => [
                'type' => 'featured',
            ],
        ]);

        $this->assertCount(
            2,
            $products,
        );
    }

    public function test_category_source_returns_only_published_products_from_selected_category(): void
    {
        $category =
            Category::factory()->create();

        $matching =
            Product::factory()->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subDay(),
            ]);

        $other =
            Product::factory()->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subDay(),
            ]);

        $draft =
            Product::factory()->create([
                'status' => ProductStatus::Draft,
                'published_at' => null,
            ]);

        $matching
            ->categories()
            ->attach($category->id);

        $draft
            ->categories()
            ->attach($category->id);

        $products = $this->resolver->resolve([
            'title' => 'Category Products',
            'limit' => 8,
            'source' => [
                'type' => 'category',
                'category_id' => $category->id,
            ],
        ]);

        $ids = $this->productIds(
            $products,
        );

        $this->assertContains(
            $matching->id,
            $ids,
        );

        $this->assertNotContains(
            $other->id,
            $ids,
        );

        $this->assertNotContains(
            $draft->id,
            $ids,
        );
    }

    public function test_category_source_respects_limit(): void
    {
        $category =
            Category::factory()->create();

        $products = Product::factory()
            ->count(4)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subDay(),
            ]);

        foreach ($products as $product) {
            $product
                ->categories()
                ->attach($category->id);
        }

        $resolved = $this->resolver->resolve([
            'title' => 'Category Products',
            'limit' => 2,
            'source' => [
                'type' => 'category',
                'category_id' => $category->id,
            ],
        ]);

        $this->assertCount(
            2,
            $resolved,
        );
    }

    public function test_manual_source_preserves_configured_order(): void
    {
        $first =
            Product::factory()->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subDays(3),
            ]);

        $second =
            Product::factory()->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subDays(2),
            ]);

        $third =
            Product::factory()->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subDay(),
            ]);

        $products = $this->resolver->resolve([
            'title' => 'Our Picks',
            'limit' => 8,
            'source' => [
                'type' => 'manual',
                'product_ids' => [
                    $third->id,
                    $first->id,
                    $second->id,
                ],
            ],
        ]);

        $this->assertSame(
            [
                $third->id,
                $first->id,
                $second->id,
            ],
            $this->productIds($products),
        );
    }

    public function test_manual_source_excludes_products_that_are_no_longer_published(): void
    {
        $published =
            Product::factory()->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subDay(),
            ]);

        $draft =
            Product::factory()->create([
                'status' => ProductStatus::Draft,
                'published_at' => null,
            ]);

        $products = $this->resolver->resolve([
            'title' => 'Our Picks',
            'limit' => 8,
            'source' => [
                'type' => 'manual',
                'product_ids' => [
                    $published->id,
                    $draft->id,
                ],
            ],
        ]);

        $this->assertSame(
            [
                $published->id,
            ],
            $this->productIds($products),
        );
    }

    public function test_manual_source_respects_limit(): void
    {
        $products = Product::factory()
            ->count(4)
            ->create([
                'status' => ProductStatus::Published,
                'published_at' => now()->subDay(),
            ]);

        $ids = $products
            ->pluck('id')
            ->map(
                static fn (
                    mixed $id,
                ): int => (int) $id,
            )
            ->all();

        $resolved = $this->resolver->resolve([
            'title' => 'Our Picks',
            'limit' => 2,
            'source' => [
                'type' => 'manual',
                'product_ids' => $ids,
            ],
        ]);

        $this->assertCount(
            2,
            $resolved,
        );

        $this->assertSame(
            array_slice(
                $ids,
                0,
                2,
            ),
            $this->productIds($resolved),
        );
    }

    public function test_resolver_returns_storefront_product_card_data(): void
    {
        Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $products = $this->resolver->resolve([
            'title' => 'Products',
            'limit' => 8,
            'source' => [
                'type' => 'latest',
            ],
        ]);

        $this->assertNotEmpty(
            $products,
        );

        $this->assertInstanceOf(
            StorefrontProductCardData::class,
            $products[0],
        );
    }

    /**
     * @param  list<StorefrontProductCardData>  $products
     * @return list<int>
     */
    private function productIds(
        array $products,
    ): array {
        return array_map(
            static fn (
                StorefrontProductCardData $product,
            ): int => $product->id,
            $products,
        );
    }
}
