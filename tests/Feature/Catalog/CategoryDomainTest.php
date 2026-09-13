<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Actions\CreateCategoryAction;
use App\Domain\Catalog\Actions\UpdateCategoryAction;
use App\Domain\Catalog\Data\CreateCategoryData;
use App\Domain\Catalog\Data\UpdateCategoryData;
use App\Domain\Catalog\Exceptions\InvalidCategoryHierarchyException;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryDomainTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_slug_is_generated_from_name(): void
    {
        $action = app(CreateCategoryAction::class);

        $category = $action->execute(
            new CreateCategoryData(
                name: 'Mobile Phones',
                slug: null,
                parentId: null,
                description: null,
                position: 0,
                isActive: true,
                metaTitle: null,
                metaDescription: null,
            ),
        );

        $this->assertSame(
            'mobile-phones',
            $category->slug,
        );
    }

    public function test_duplicate_generated_slugs_are_made_unique(): void
    {
        $action = app(CreateCategoryAction::class);

        $action->execute(
            new CreateCategoryData(
                name: 'Mobile Phones',
                slug: null,
                parentId: null,
                description: null,
                position: 0,
                isActive: true,
                metaTitle: null,
                metaDescription: null,
            ),
        );

        $second = $action->execute(
            new CreateCategoryData(
                name: 'Mobile Phones',
                slug: null,
                parentId: null,
                description: null,
                position: 0,
                isActive: true,
                metaTitle: null,
                metaDescription: null,
            ),
        );

        $this->assertSame(
            'mobile-phones-2',
            $second->slug,
        );
    }

    public function test_category_cannot_be_its_own_parent(): void
    {
        $category = Category::factory()->create();

        $action = app(UpdateCategoryAction::class);

        $this->expectException(
            InvalidCategoryHierarchyException::class,
        );

        $action->execute(
            $category,
            new UpdateCategoryData(
                name: $category->name,
                slug: $category->slug,
                parentId: $category->id,
                description: $category->description,
                position: $category->position,
                isActive: $category->is_active,
                metaTitle: $category->meta_title,
                metaDescription: $category->meta_description,
            ),
        );
    }

    public function test_category_cannot_be_moved_inside_its_descendant(): void
    {
        $parent = Category::factory()->create();

        $child = Category::factory()->create([
            'parent_id' => $parent->id,
        ]);

        $grandchild = Category::factory()->create([
            'parent_id' => $child->id,
        ]);

        $action = app(UpdateCategoryAction::class);

        $this->expectException(
            InvalidCategoryHierarchyException::class,
        );

        $action->execute(
            $parent,
            new UpdateCategoryData(
                name: $parent->name,
                slug: $parent->slug,
                parentId: $grandchild->id,
                description: $parent->description,
                position: $parent->position,
                isActive: $parent->is_active,
                metaTitle: $parent->meta_title,
                metaDescription: $parent->meta_description,
            ),
        );
    }
}
