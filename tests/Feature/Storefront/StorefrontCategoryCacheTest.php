<?php

declare(strict_types=1);

namespace Tests\Feature\Storefront;

use App\Domain\Catalog\Queries\Storefront\StorefrontCategoryQuery;
use App\Models\Category;
use App\Support\Cache\StorefrontCatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class StorefrontCategoryCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_active_category_can_be_resolved(): void
    {
        $category = Category::factory()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'is_active' => true,
        ]);

        $resolved = app(
            StorefrontCategoryQuery::class,
        )->findBySlugOrFail('phones');

        $this->assertSame(
            $category->id,
            $resolved->id,
        );
    }

    public function test_active_category_is_cached(): void
    {
        $category = Category::factory()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'is_active' => true,
        ]);

        $query = app(
            StorefrontCategoryQuery::class,
        );

        $first = $query->findBySlugOrFail(
            'phones',
        );

        Category::query()
            ->whereKey($category->id)
            ->update([
                'name' => 'Changed in database',
            ]);

        $second = $query->findBySlugOrFail(
            'phones',
        );

        $this->assertSame(
            'Phones',
            $first->name,
        );

        $this->assertSame(
            'Phones',
            $second->name,
        );
    }

    public function test_cache_invalidation_exposes_fresh_category_data(): void
    {
        $category = Category::factory()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'is_active' => true,
        ]);

        $query = app(
            StorefrontCategoryQuery::class,
        );

        $query->findBySlugOrFail(
            'phones',
        );

        Category::query()
            ->whereKey($category->id)
            ->update([
                'name' => 'Smartphones',
            ]);

        app(
            StorefrontCatalogCache::class,
        )->invalidate();

        $fresh = $query->findBySlugOrFail(
            'phones',
        );

        $this->assertSame(
            'Smartphones',
            $fresh->name,
        );
    }

    public function test_inactive_category_returns_404(): void
    {
        Category::factory()->create([
            'slug' => 'hidden-category',
            'is_active' => false,
        ]);

        $this->expectException(
            HttpException::class,
        );

        app(
            StorefrontCategoryQuery::class,
        )->findBySlugOrFail(
            'hidden-category',
        );
    }

    public function test_missing_category_returns_404(): void
    {
        $this->expectException(
            HttpException::class,
        );

        app(
            StorefrontCategoryQuery::class,
        )->findBySlugOrFail(
            'does-not-exist',
        );
    }

    public function test_missing_category_is_not_negatively_cached(): void
    {
        $query = app(
            StorefrontCategoryQuery::class,
        );

        try {
            $query->findBySlugOrFail(
                'future-category',
            );
        } catch (HttpException) {
            // Expected.
        }

        Category::factory()->create([
            'name' => 'Future Category',
            'slug' => 'future-category',
            'is_active' => true,
        ]);

        $category = $query->findBySlugOrFail(
            'future-category',
        );

        $this->assertSame(
            'Future Category',
            $category->name,
        );
    }
}
