<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Actions\CreateProductAction;
use App\Domain\Catalog\Actions\UpdateProductAction;
use App\Domain\Catalog\Data\CreateProductData;
use App\Domain\Catalog\Data\UpdateProductData;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductPivotIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created_with_multiple_categories(): void
    {
        $categories = Category::factory()
            ->count(3)
            ->create();

        $data = new CreateProductData(
            brandId: null,
            name: 'Test Product',
            slug: null,
            shortDescription: null,
            description: null,
            status: ProductStatus::Draft,
            isFeatured: false,
            position: 0,
            publishedAt: null,
            metaTitle: null,
            metaDescription: null,
            categoryIds: $categories
                ->pluck('id')
                ->all(),
        );

        $product = app(
            CreateProductAction::class,
        )->execute($data);

        $this->assertEqualsCanonicalizing(
            $categories
                ->pluck('id')
                ->all(),
            $product
                ->categories()
                ->pluck('categories.id')
                ->all(),
        );
    }

    public function test_updating_product_synchronizes_categories(): void
    {
        $first = Category::factory()->create();
        $second = Category::factory()->create();
        $third = Category::factory()->create();

        $product = Product::factory()->create();

        $product
            ->categories()
            ->sync([
                $first->id,
                $second->id,
            ]);

        $data = new UpdateProductData(
            brandId: $product->brand_id,
            name: $product->name,
            slug: $product->slug,
            shortDescription: $product->short_description,
            description: $product->description,
            status: $product->status,
            isFeatured: $product->is_featured,
            position: $product->position,
            publishedAt: $product->published_at,
            metaTitle: $product->meta_title,
            metaDescription: $product->meta_description,

            categoryIds: [
                $second->id,
                $third->id,
            ],
        );

        app(
            UpdateProductAction::class,
        )->execute(
            $product,
            $data,
        );

        $this->assertEqualsCanonicalizing(
            [
                $second->id,
                $third->id,
            ],
            $product
                ->categories()
                ->pluck('categories.id')
                ->all(),
        );

        $this->assertDatabaseMissing(
            'category_product',
            [
                'product_id' => $product->id,

                'category_id' => $first->id,
            ],
        );
    }

    public function test_product_can_have_all_categories_removed(): void
    {
        $categories = Category::factory()
            ->count(2)
            ->create();

        $product = Product::factory()->create();

        $product
            ->categories()
            ->sync(
                $categories
                    ->pluck('id')
                    ->all(),
            );

        $data = new UpdateProductData(
            brandId: $product->brand_id,
            name: $product->name,
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

        app(
            UpdateProductAction::class,
        )->execute(
            $product,
            $data,
        );

        $this->assertSame(
            0,
            $product
                ->categories()
                ->count(),
        );

        $this->assertDatabaseMissing(
            'category_product',
            [
                'product_id' => $product->id,
            ],
        );
    }

    public function test_deleting_category_removes_its_product_pivot_only(): void
    {
        $first = Category::factory()->create();
        $second = Category::factory()->create();

        $product = Product::factory()->create();

        $product
            ->categories()
            ->sync([
                $first->id,
                $second->id,
            ]);

        $first->delete();

        $this->assertDatabaseMissing(
            'category_product',
            [
                'product_id' => $product->id,

                'category_id' => $first->id,
            ],
        );

        $this->assertDatabaseHas(
            'category_product',
            [
                'product_id' => $product->id,

                'category_id' => $second->id,
            ],
        );

        $this->assertDatabaseHas(
            'products',
            [
                'id' => $product->id,
            ],
        );
    }
}
