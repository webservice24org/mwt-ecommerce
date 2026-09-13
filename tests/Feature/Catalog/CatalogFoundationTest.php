<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_can_have_a_parent(): void
    {
        $parent = Category::factory()->create();

        $child = Category::factory()->create([
            'parent_id' => $parent->id,
        ]);

        $this->assertTrue(
            $child->parent->is($parent),
        );

        $this->assertTrue(
            $parent->children->contains($child),
        );
    }

    public function test_deleting_parent_category_does_not_delete_child(): void
    {
        $parent = Category::factory()->create();

        $child = Category::factory()->create([
            'parent_id' => $parent->id,
        ]);

        $parent->delete();

        $child->refresh();

        $this->assertNull(
            $child->parent_id,
        );
    }

    public function test_category_boolean_and_position_casts_are_correct(): void
    {
        $category = Category::factory()->create([
            'is_active' => true,
            'position' => 5,
        ]);

        $this->assertTrue(
            $category->is_active,
        );

        $this->assertSame(
            5,
            $category->position,
        );
    }

    public function test_brand_can_be_created(): void
    {
        $brand = Brand::factory()->create([
            'name' => 'Example Brand',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Example Brand',
            'is_active' => true,
        ]);
    }
}
