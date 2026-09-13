<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_category_management(): void
    {
        $this
            ->get(route('admin.categories.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_editor_can_view_categories(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->get(route('admin.categories.index'))
            ->assertOk();
    }

    public function test_editor_can_create_category(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route('admin.categories.store'),
                [
                    'parent_id' => null,
                    'name' => 'Mobile Phones',
                    'slug' => '',
                    'description' => null,
                    'image_path' => null,
                    'position' => 0,
                    'is_active' => true,
                    'meta_title' => null,
                    'meta_description' => null,
                ],
            )
            ->assertRedirect(
                route('admin.categories.index'),
            );

        $this->assertDatabaseHas(
            'categories',
            [
                'name' => 'Mobile Phones',
                'slug' => 'mobile-phones',
            ],
        );
    }

    public function test_editor_cannot_delete_category(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $category = Category::factory()->create();

        $this
            ->actingAs($admin, 'admin')
            ->delete(
                route(
                    'admin.categories.destroy',
                    $category,
                ),
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'categories',
            [
                'id' => $category->id,
            ],
        );
    }

    public function test_manager_can_delete_category(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $category = Category::factory()->create();

        $this
            ->actingAs($admin, 'admin')
            ->delete(
                route(
                    'admin.categories.destroy',
                    $category,
                ),
            )
            ->assertRedirect(
                route('admin.categories.index'),
            );

        $this->assertDatabaseMissing(
            'categories',
            [
                'id' => $category->id,
            ],
        );
    }

    public function test_category_cannot_use_itself_as_parent(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $category = Category::factory()->create();

        $this
            ->actingAs($admin, 'admin')
            ->put(
                route(
                    'admin.categories.update',
                    $category,
                ),
                [
                    'parent_id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => null,
                    'image_path' => null,
                    'position' => 0,
                    'is_active' => true,
                    'meta_title' => null,
                    'meta_description' => null,
                ],
            )
            ->assertSessionHasErrors(
                'parent_id',
            );
    }

    public function test_category_cannot_be_moved_under_descendant(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $parent = Category::factory()->create();

        $child = Category::factory()->create([
            'parent_id' => $parent->id,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->put(
                route(
                    'admin.categories.update',
                    $parent,
                ),
                [
                    'parent_id' => $child->id,
                    'name' => $parent->name,
                    'slug' => $parent->slug,
                    'description' => null,
                    'image_path' => null,
                    'position' => 0,
                    'is_active' => true,
                    'meta_title' => null,
                    'meta_description' => null,
                ],
            )
            ->assertSessionHas('error');

        $parent->refresh();

        $this->assertNull(
            $parent->parent_id,
        );
    }

    public function test_category_image_can_be_uploaded(): void
    {
        Storage::fake('public');

        $admin = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $image = UploadedFile::fake()->image(
            'category.jpg',
            800,
            600,
        );

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route('admin.categories.store'),
                [
                    'parent_id' => null,
                    'name' => 'Mobile Phones',
                    'slug' => '',
                    'description' => null,
                    'image' => $image,
                    'position' => 0,
                    'is_active' => true,
                    'meta_title' => null,
                    'meta_description' => null,
                ],
            )
            ->assertRedirect(
                route('admin.categories.index'),
            );

        $category = Category::query()
            ->where('name', 'Mobile Phones')
            ->firstOrFail();

        $this->assertNotNull(
            $category->image_path,
        );

        Storage::disk('public')->assertExists(
            $category->image_path,
        );
    }

    public function test_replacing_category_image_deletes_old_image(): void
    {
        Storage::fake('public');

        $admin = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $oldImage = UploadedFile::fake()
            ->image('old.jpg');

        $oldPath = $oldImage->store(
            'catalog/categories',
            'public',
        );

        $category = Category::factory()->create([
            'image_path' => $oldPath,
        ]);

        $newImage = UploadedFile::fake()
            ->image('new.jpg');

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route(
                    'admin.categories.update',
                    $category,
                ),
                [
                    '_method' => 'put',
                    'parent_id' => null,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => null,
                    'image' => $newImage,
                    'remove_image' => false,
                    'position' => 0,
                    'is_active' => true,
                    'meta_title' => null,
                    'meta_description' => null,
                ],
            )
            ->assertRedirect(
                route('admin.categories.index'),
            );

        $category->refresh();

        Storage::disk('public')->assertMissing(
            $oldPath,
        );

        $this->assertNotNull(
            $category->image_path,
        );

        Storage::disk('public')->assertExists(
            $category->image_path,
        );
    }

    public function test_category_image_can_be_removed(): void
    {
        Storage::fake('public');

        $admin = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $image = UploadedFile::fake()
            ->image('category.jpg');

        $path = $image->store(
            'catalog/categories',
            'public',
        );

        $category = Category::factory()->create([
            'image_path' => $path,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route(
                    'admin.categories.update',
                    $category,
                ),
                [
                    '_method' => 'put',
                    'parent_id' => null,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => null,
                    'remove_image' => true,
                    'position' => 0,
                    'is_active' => true,
                    'meta_title' => null,
                    'meta_description' => null,
                ],
            );

        $category->refresh();

        $this->assertNull(
            $category->image_path,
        );

        Storage::disk('public')->assertMissing(
            $path,
        );
    }

    public function test_updating_category_without_image_keeps_existing_image(): void
    {
        Storage::fake('public');

        $admin = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $image = UploadedFile::fake()
            ->image('category.jpg');

        $path = $image->store(
            'catalog/categories',
            'public',
        );

        $category = Category::factory()->create([
            'image_path' => $path,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->put(
                route(
                    'admin.categories.update',
                    $category,
                ),
                [
                    'parent_id' => null,
                    'name' => 'Updated Category',
                    'slug' => $category->slug,
                    'description' => null,
                    'remove_image' => false,
                    'position' => 0,
                    'is_active' => true,
                    'meta_title' => null,
                    'meta_description' => null,
                ],
            );

        $category->refresh();

        $this->assertSame(
            $path,
            $category->image_path,
        );

        Storage::disk('public')->assertExists(
            $path,
        );
    }

    public function test_deleting_category_deletes_its_image(): void
    {
        Storage::fake('public');

        $admin = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $image = UploadedFile::fake()
            ->image('category.jpg');

        $path = $image->store(
            'catalog/categories',
            'public',
        );

        $category = Category::factory()->create([
            'image_path' => $path,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->delete(
                route(
                    'admin.categories.destroy',
                    $category,
                ),
            )
            ->assertRedirect(
                route('admin.categories.index'),
            );

        $this->assertDatabaseMissing(
            'categories',
            [
                'id' => $category->id,
            ],
        );

        Storage::disk('public')->assertMissing(
            $path,
        );
    }
}
