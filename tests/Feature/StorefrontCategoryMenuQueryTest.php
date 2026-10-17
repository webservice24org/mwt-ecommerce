<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Catalog\Queries\Storefront\StorefrontCategoryMenuQuery;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StorefrontCategoryMenuQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_active_root_categories_with_active_children(): void
    {
        $root = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'parent_id' => null,
            'position' => 1,
            'is_active' => true,
        ]);

        $child = Category::factory()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'parent_id' => $root->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $result = app(
            StorefrontCategoryMenuQuery::class,
        )->get();

        $this->assertCount(
            1,
            $result,
        );

        $this->assertSame(
            $root->id,
            $result[0]['id'],
        );

        $this->assertSame(
            'Electronics',
            $result[0]['name'],
        );

        $this->assertSame(
            'electronics',
            $result[0]['slug'],
        );

        $this->assertCount(
            1,
            $result[0]['children'],
        );

        $this->assertSame(
            $child->id,
            $result[0]['children'][0]['id'],
        );

        $this->assertSame(
            'Phones',
            $result[0]['children'][0]['name'],
        );

        $this->assertSame(
            'phones',
            $result[0]['children'][0]['slug'],
        );

        $this->assertSame(
            [],
            $result[0]['children'][0]['children'],
        );
    }

    public function test_it_excludes_inactive_root_categories(): void
    {
        Category::factory()->create([
            'name' => 'Visible',
            'slug' => 'visible',
            'parent_id' => null,
            'position' => 1,
            'is_active' => true,
        ]);

        Category::factory()->create([
            'name' => 'Hidden',
            'slug' => 'hidden',
            'parent_id' => null,
            'position' => 2,
            'is_active' => false,
        ]);

        $result = app(
            StorefrontCategoryMenuQuery::class,
        )->get();

        $this->assertCount(
            1,
            $result,
        );

        $this->assertSame(
            'Visible',
            $result[0]['name'],
        );
    }

    public function test_it_excludes_inactive_children(): void
    {
        $root = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'parent_id' => null,
            'position' => 1,
            'is_active' => true,
        ]);

        Category::factory()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'parent_id' => $root->id,
            'position' => 1,
            'is_active' => true,
        ]);

        Category::factory()->create([
            'name' => 'Hidden Phones',
            'slug' => 'hidden-phones',
            'parent_id' => $root->id,
            'position' => 2,
            'is_active' => false,
        ]);

        $result = app(
            StorefrontCategoryMenuQuery::class,
        )->get();

        $this->assertCount(
            1,
            $result[0]['children'],
        );

        $this->assertSame(
            'Phones',
            $result[0]['children'][0]['name'],
        );
    }

    public function test_it_orders_root_categories_by_position_then_id(): void
    {
        Category::factory()->create([
            'name' => 'Third',
            'slug' => 'third',
            'parent_id' => null,
            'position' => 30,
            'is_active' => true,
        ]);

        $first = Category::factory()->create([
            'name' => 'First',
            'slug' => 'first',
            'parent_id' => null,
            'position' => 10,
            'is_active' => true,
        ]);

        $second = Category::factory()->create([
            'name' => 'Second',
            'slug' => 'second',
            'parent_id' => null,
            'position' => 20,
            'is_active' => true,
        ]);

        $result = app(
            StorefrontCategoryMenuQuery::class,
        )->get();

        $this->assertSame(
            [
                $first->id,
                $second->id,
            ],
            [
                $result[0]['id'],
                $result[1]['id'],
            ],
        );

        $this->assertSame(
            'Third',
            $result[2]['name'],
        );
    }

    public function test_it_orders_children_by_position_then_id(): void
    {
        $root = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'parent_id' => null,
            'position' => 1,
            'is_active' => true,
        ]);

        Category::factory()->create([
            'name' => 'Third',
            'slug' => 'third',
            'parent_id' => $root->id,
            'position' => 30,
            'is_active' => true,
        ]);

        $first = Category::factory()->create([
            'name' => 'First',
            'slug' => 'first',
            'parent_id' => $root->id,
            'position' => 10,
            'is_active' => true,
        ]);

        $second = Category::factory()->create([
            'name' => 'Second',
            'slug' => 'second',
            'parent_id' => $root->id,
            'position' => 20,
            'is_active' => true,
        ]);

        $result = app(
            StorefrontCategoryMenuQuery::class,
        )->get();

        $this->assertSame(
            [
                $first->id,
                $second->id,
            ],
            [
                $result[0]['children'][0]['id'],
                $result[0]['children'][1]['id'],
            ],
        );

        $this->assertSame(
            'Third',
            $result[0]['children'][2]['name'],
        );
    }

    public function test_it_attaches_children_to_the_correct_root_category(): void
    {
        $electronics = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'parent_id' => null,
            'position' => 1,
            'is_active' => true,
        ]);

        $fashion = Category::factory()->create([
            'name' => 'Fashion',
            'slug' => 'fashion',
            'parent_id' => null,
            'position' => 2,
            'is_active' => true,
        ]);

        Category::factory()->create([
            'name' => 'Phones',
            'slug' => 'phones',
            'parent_id' => $electronics->id,
            'position' => 1,
            'is_active' => true,
        ]);

        Category::factory()->create([
            'name' => 'Shirts',
            'slug' => 'shirts',
            'parent_id' => $fashion->id,
            'position' => 1,
            'is_active' => true,
        ]);

        $result = app(
            StorefrontCategoryMenuQuery::class,
        )->get();

        $this->assertSame(
            'Phones',
            $result[0]['children'][0]['name'],
        );

        $this->assertSame(
            'Shirts',
            $result[1]['children'][0]['name'],
        );
    }

    public function test_it_limits_root_categories_to_six(): void
    {
        foreach (
            range(1, 8) as $position
        ) {
            Category::factory()->create([
                'name' => "Category {$position}",
                'slug' => "category-{$position}",
                'parent_id' => null,
                'position' => $position,
                'is_active' => true,
            ]);
        }

        $result = app(
            StorefrontCategoryMenuQuery::class,
        )->get();

        $this->assertCount(
            6,
            $result,
        );

        $this->assertSame(
            'Category 1',
            $result[0]['name'],
        );

        $this->assertSame(
            'Category 6',
            $result[5]['name'],
        );
    }

    public function test_it_limits_children_to_twelve_per_root_category(): void
    {
        $root = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'parent_id' => null,
            'position' => 1,
            'is_active' => true,
        ]);

        foreach (
            range(1, 14) as $position
        ) {
            Category::factory()->create([
                'name' => "Child {$position}",
                'slug' => "child-{$position}",
                'parent_id' => $root->id,
                'position' => $position,
                'is_active' => true,
            ]);
        }

        $result = app(
            StorefrontCategoryMenuQuery::class,
        )->get();

        $this->assertCount(
            12,
            $result[0]['children'],
        );

        $this->assertSame(
            'Child 1',
            $result[0]['children'][0]['name'],
        );

        $this->assertSame(
            'Child 12',
            $result[0]['children'][11]['name'],
        );
    }

    public function test_it_returns_an_empty_array_when_no_active_categories_exist(): void
    {
        Category::factory()->create([
            'name' => 'Inactive',
            'slug' => 'inactive',
            'parent_id' => null,
            'position' => 1,
            'is_active' => false,
        ]);

        $result = app(
            StorefrontCategoryMenuQuery::class,
        )->get();

        $this->assertSame(
            [],
            $result,
        );
    }
}
