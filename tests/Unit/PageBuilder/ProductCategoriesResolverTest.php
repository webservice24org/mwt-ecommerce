<?php

declare(strict_types=1);

namespace Tests\Unit\PageBuilder;

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\PageBuilder\Resolvers\ProductCategoriesResolver;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

final class ProductCategoriesResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_selected_categories_in_configured_order(): void
    {
        $first = Category::factory()->create([
            'name' => 'First Category',
            'slug' => 'first-category',
        ]);

        $second = Category::factory()->create([
            'name' => 'Second Category',
            'slug' => 'second-category',
        ]);

        $third = Category::factory()->create([
            'name' => 'Third Category',
            'slug' => 'third-category',
        ]);

        $categories = $this
            ->resolver()
            ->resolve([
                'category_ids' => [
                    $third->id,
                    $first->id,
                    $second->id,
                ],
                'show_product_count' => false,
            ]);

        $this->assertCount(
            3,
            $categories,
        );

        $this->assertSame(
            [
                $third->id,
                $first->id,
                $second->id,
            ],
            array_map(
                static fn ($category): int => $category->id,
                $categories,
            ),
        );

        $this->assertSame(
            'Third Category',
            $categories[0]->name,
        );

        $this->assertSame(
            'third-category',
            $categories[0]->slug,
        );
    }

    public function test_it_omits_inactive_categories(): void
    {
        $active = Category::factory()->create();

        $inactive = Category::factory()
            ->inactive()
            ->create();

        $categories = $this
            ->resolver()
            ->resolve([
                'category_ids' => [
                    $inactive->id,
                    $active->id,
                ],
                'show_product_count' => false,
            ]);

        $this->assertCount(
            1,
            $categories,
        );

        $this->assertSame(
            $active->id,
            $categories[0]->id,
        );
    }

    public function test_it_omits_missing_categories(): void
    {
        $category = Category::factory()->create();

        $categories = $this
            ->resolver()
            ->resolve([
                'category_ids' => [
                    999999,
                    $category->id,
                ],
                'show_product_count' => false,
            ]);

        $this->assertCount(
            1,
            $categories,
        );

        $this->assertSame(
            $category->id,
            $categories[0]->id,
        );
    }

    public function test_empty_category_selection_returns_empty_array(): void
    {
        $categories = $this
            ->resolver()
            ->resolve([
                'category_ids' => [],
                'show_product_count' => false,
            ]);

        $this->assertSame(
            [],
            $categories,
        );
    }

    public function test_it_resolves_category_description(): void
    {
        $category = Category::factory()->create([
            'description' => 'Products for the home.',
        ]);

        $categories = $this
            ->resolver()
            ->resolve([
                'category_ids' => [
                    $category->id,
                ],
                'show_product_count' => false,
            ]);

        $this->assertSame(
            'Products for the home.',
            $categories[0]->description,
        );
    }

    public function test_it_resolves_public_category_image_url(): void
    {
        Storage::fake('public');

        $category = Category::factory()->create([
            'image_path' => 'categories/example.jpg',
        ]);

        $categories = $this
            ->resolver()
            ->resolve([
                'category_ids' => [
                    $category->id,
                ],
                'show_product_count' => false,
            ]);

        $this->assertSame(
            Storage::disk('public')->url(
                'categories/example.jpg',
            ),
            $categories[0]->imageUrl,
        );
    }

    public function test_category_without_image_has_null_image_url(): void
    {
        $category = Category::factory()->create([
            'image_path' => null,
        ]);

        $categories = $this
            ->resolver()
            ->resolve([
                'category_ids' => [
                    $category->id,
                ],
                'show_product_count' => false,
            ]);

        $this->assertNull(
            $categories[0]->imageUrl,
        );
    }

    public function test_product_count_is_null_when_disabled(): void
    {
        $category = Category::factory()->create();

        $product = Product::factory()
            ->published()
            ->create();

        $category
            ->products()
            ->attach($product);

        $categories = $this
            ->resolver()
            ->resolve([
                'category_ids' => [
                    $category->id,
                ],
                'show_product_count' => false,
            ]);

        $this->assertNull(
            $categories[0]->productCount,
        );
    }

    public function test_product_count_includes_only_published_products(): void
    {
        $category = Category::factory()->create();

        $publishedOne = Product::factory()
            ->published()
            ->create([
                'published_at' => now()->subDays(2),
            ]);

        $publishedTwo = Product::factory()
            ->published()
            ->create([
                'published_at' => now()->subDay(),
            ]);

        $draft = Product::factory()->create();

        $future = Product::factory()
            ->published()
            ->create([
                'published_at' => now()->addDay(),
            ]);

        $category
            ->products()
            ->attach([
                $publishedOne->id,
                $publishedTwo->id,
                $draft->id,
                $future->id,
            ]);

        $categories = $this
            ->resolver()
            ->resolve([
                'category_ids' => [
                    $category->id,
                ],
                'show_product_count' => true,
            ]);

        $this->assertSame(
            2,
            $categories[0]->productCount,
        );
    }

    public function test_published_product_with_null_published_at_is_counted(): void
    {
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'status' => ProductStatus::Published,
            'published_at' => null,
        ]);

        $category
            ->products()
            ->attach($product);

        $categories = $this
            ->resolver()
            ->resolve([
                'category_ids' => [
                    $category->id,
                ],
                'show_product_count' => true,
            ]);

        $this->assertSame(
            1,
            $categories[0]->productCount,
        );
    }

    public function test_category_data_serializes_to_public_contract(): void
    {
        $category = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'description' => 'Electronic products.',
            'image_path' => null,
        ]);

        $categories = $this
            ->resolver()
            ->resolve([
                'category_ids' => [
                    $category->id,
                ],
                'show_product_count' => false,
            ]);

        $this->assertSame(
            [
                'id' => $category->id,
                'name' => 'Electronics',
                'slug' => 'electronics',
                'description' => 'Electronic products.',
                'image_url' => null,
                'product_count' => null,
            ],
            $categories[0]->toArray(),
        );
    }

    public function test_invalid_category_ids_configuration_throws_logic_exception(): void
    {
        $this->expectException(
            LogicException::class,
        );

        $this
            ->resolver()
            ->resolve([
                'category_ids' => 'invalid',
                'show_product_count' => false,
            ]);
    }

    public function test_invalid_category_id_throws_logic_exception(): void
    {
        $this->expectException(
            LogicException::class,
        );

        $this
            ->resolver()
            ->resolve([
                'category_ids' => [
                    0,
                ],
                'show_product_count' => false,
            ]);
    }

    public function test_invalid_show_product_count_configuration_throws_logic_exception(): void
    {
        $category = Category::factory()->create();

        $this->expectException(
            LogicException::class,
        );

        $this
            ->resolver()
            ->resolve([
                'category_ids' => [
                    $category->id,
                ],
                'show_product_count' => 'yes',
            ]);
    }

    private function resolver(): ProductCategoriesResolver
    {
        return app(
            ProductCategoriesResolver::class,
        );
    }
}
