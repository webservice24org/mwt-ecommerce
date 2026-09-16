<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Queries\ProductFormOptionsQuery;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductFormOptionsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_options_only_include_active_brands_and_categories(): void
    {
        $activeBrand = Brand::factory()->create([
            'is_active' => true,
        ]);

        $inactiveBrand = Brand::factory()->create([
            'is_active' => false,
        ]);

        $activeCategory = Category::factory()->create([
            'is_active' => true,
        ]);

        $inactiveCategory = Category::factory()->create([
            'is_active' => false,
        ]);

        $options = app(
            ProductFormOptionsQuery::class,
        )->get();

        $brandIds = collect($options['brands'])
            ->pluck('id')
            ->all();

        $categoryIds = collect($options['categories'])
            ->pluck('id')
            ->all();

        $this->assertContains(
            $activeBrand->id,
            $brandIds,
        );

        $this->assertNotContains(
            $inactiveBrand->id,
            $brandIds,
        );

        $this->assertContains(
            $activeCategory->id,
            $categoryIds,
        );

        $this->assertNotContains(
            $inactiveCategory->id,
            $categoryIds,
        );
    }

    public function test_edit_options_include_current_inactive_brand(): void
    {
        $currentBrand = Brand::factory()->create([
            'is_active' => false,
        ]);

        $otherInactiveBrand = Brand::factory()->create([
            'is_active' => false,
        ]);

        $product = Product::factory()->create([
            'brand_id' => $currentBrand->id,
        ]);

        $options = app(
            ProductFormOptionsQuery::class,
        )->get(
            $product,
        );

        $brandIds = collect($options['brands'])
            ->pluck('id')
            ->all();

        $this->assertContains(
            $currentBrand->id,
            $brandIds,
        );

        $this->assertNotContains(
            $otherInactiveBrand->id,
            $brandIds,
        );
    }

    public function test_edit_options_include_current_inactive_categories(): void
    {
        $currentCategory = Category::factory()->create([
            'is_active' => false,
        ]);

        $otherInactiveCategory = Category::factory()->create([
            'is_active' => false,
        ]);

        $product = Product::factory()->create();

        $product->categories()->attach(
            $currentCategory->id,
        );

        $options = app(
            ProductFormOptionsQuery::class,
        )->get(
            $product,
        );

        $categoryIds = collect($options['categories'])
            ->pluck('id')
            ->all();

        $this->assertContains(
            $currentCategory->id,
            $categoryIds,
        );

        $this->assertNotContains(
            $otherInactiveCategory->id,
            $categoryIds,
        );
    }
}
