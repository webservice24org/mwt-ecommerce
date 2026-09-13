<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\AttributeValue;
use App\Models\ProductAttribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AttributeAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_attribute_admin(): void
    {
        $this->get(
            route('admin.attributes.index'),
        )->assertRedirect(
            route('admin.login'),
        );
    }

    public function test_admin_can_view_attribute_index(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        ProductAttribute::factory()
            ->create([
                'name' => 'Color',
                'slug' => 'color',
            ]);

        $this->actingAs(
            $admin,
            'admin',
        )
            ->get(
                route('admin.attributes.index'),
            )
            ->assertOk();
    }

    public function test_admin_can_create_attribute(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->post(
                route('admin.attributes.store'),
                [
                    'name' => 'Color',
                    'slug' => '',
                    'position' => 10,
                    'is_active' => true,
                ],
            );

        $attribute = ProductAttribute::query()
            ->where('name', 'Color')
            ->firstOrFail();

        $response->assertRedirect(
            route(
                'admin.attributes.edit',
                $attribute,
            ),
        );

        $this->assertDatabaseHas(
            'attributes',
            [
                'id' => $attribute->id,
                'name' => 'Color',
                'slug' => 'color',
                'position' => 10,
                'is_active' => true,
            ],
        );
    }

    public function test_duplicate_attribute_slug_gets_numeric_suffix(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        ProductAttribute::factory()
            ->create([
                'name' => 'Color',
                'slug' => 'color',
            ]);

        $this->actingAs(
            $admin,
            'admin',
        )
            ->post(
                route('admin.attributes.store'),
                [
                    'name' => 'Another Color',
                    'slug' => 'color',
                    'position' => 0,
                    'is_active' => true,
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'attributes',
            [
                'name' => 'Another Color',
                'slug' => 'color-2',
            ],
        );
    }

    public function test_manager_can_update_attribute(): void
    {
        $manager = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $attribute = ProductAttribute::factory()
            ->create([
                'name' => 'Color',
                'slug' => 'color',
            ]);

        $this->actingAs(
            $manager,
            'admin',
        )
            ->put(
                route(
                    'admin.attributes.update',
                    $attribute,
                ),
                [
                    'name' => 'Product Color',
                    'slug' => 'product-color',
                    'position' => 5,
                    'is_active' => false,
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'attributes',
            [
                'id' => $attribute->id,
                'name' => 'Product Color',
                'slug' => 'product-color',
                'position' => 5,
                'is_active' => false,
            ],
        );
    }

    public function test_editor_can_create_attribute(): void
    {
        $editor = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $this->actingAs(
            $editor,
            'admin',
        )
            ->post(
                route('admin.attributes.store'),
                [
                    'name' => 'Size',
                    'slug' => '',
                    'position' => 0,
                    'is_active' => true,
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'attributes',
            [
                'name' => 'Size',
                'slug' => 'size',
            ],
        );
    }

    public function test_editor_can_update_attribute(): void
    {
        $editor = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $attribute = ProductAttribute::factory()
            ->create([
                'name' => 'Size',
                'slug' => 'size',
            ]);

        $this->actingAs(
            $editor,
            'admin',
        )
            ->put(
                route(
                    'admin.attributes.update',
                    $attribute,
                ),
                [
                    'name' => 'Clothing Size',
                    'slug' => '',
                    'position' => 1,
                    'is_active' => true,
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'attributes',
            [
                'id' => $attribute->id,
                'name' => 'Clothing Size',
                'slug' => 'clothing-size',
            ],
        );
    }

    public function test_editor_cannot_delete_attribute(): void
    {
        $editor = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $attribute = ProductAttribute::factory()
            ->create();

        $this->actingAs(
            $editor,
            'admin',
        )
            ->delete(
                route(
                    'admin.attributes.destroy',
                    $attribute,
                ),
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'attributes',
            [
                'id' => $attribute->id,
            ],
        );
    }

    public function test_manager_can_delete_attribute(): void
    {
        $manager = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $attribute = ProductAttribute::factory()
            ->create();

        $this->actingAs(
            $manager,
            'admin',
        )
            ->delete(
                route(
                    'admin.attributes.destroy',
                    $attribute,
                ),
            )
            ->assertRedirect(
                route('admin.attributes.index'),
            );

        $this->assertDatabaseMissing(
            'attributes',
            [
                'id' => $attribute->id,
            ],
        );
    }

    public function test_attribute_can_have_values(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $attribute = ProductAttribute::factory()
            ->create([
                'name' => 'Color',
                'slug' => 'color',
            ]);

        $this->actingAs(
            $admin,
            'admin',
        )
            ->post(
                route(
                    'admin.attributes.values.store',
                    $attribute,
                ),
                [
                    'name' => 'Red',
                    'slug' => '',
                    'position' => 0,
                    'is_active' => true,
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'attribute_values',
            [
                'attribute_id' => $attribute->id,
                'name' => 'Red',
                'slug' => 'red',
                'position' => 0,
                'is_active' => true,
            ],
        );
    }

    public function test_duplicate_value_slug_within_same_attribute_gets_numeric_suffix(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $attribute = ProductAttribute::factory()
            ->create();

        AttributeValue::factory()
            ->create([
                'attribute_id' => $attribute->id,
                'name' => 'Red',
                'slug' => 'red',
            ]);

        $this->actingAs(
            $admin,
            'admin',
        )
            ->post(
                route(
                    'admin.attributes.values.store',
                    $attribute,
                ),
                [
                    'name' => 'Another Red',
                    'slug' => 'red',
                    'position' => 1,
                    'is_active' => true,
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'attribute_values',
            [
                'attribute_id' => $attribute->id,
                'name' => 'Another Red',
                'slug' => 'red-2',
            ],
        );
    }

    public function test_same_value_slug_can_exist_under_different_attributes(): void
    {
        $color = ProductAttribute::factory()
            ->create([
                'name' => 'Color',
                'slug' => 'color',
            ]);

        $material = ProductAttribute::factory()
            ->create([
                'name' => 'Material',
                'slug' => 'material',
            ]);

        AttributeValue::factory()
            ->create([
                'attribute_id' => $color->id,
                'name' => 'Natural',
                'slug' => 'natural',
            ]);

        AttributeValue::factory()
            ->create([
                'attribute_id' => $material->id,
                'name' => 'Natural',
                'slug' => 'natural',
            ]);

        $this->assertDatabaseHas(
            'attribute_values',
            [
                'attribute_id' => $color->id,
                'slug' => 'natural',
            ],
        );

        $this->assertDatabaseHas(
            'attribute_values',
            [
                'attribute_id' => $material->id,
                'slug' => 'natural',
            ],
        );

        $this->assertSame(
            2,
            AttributeValue::query()
                ->where('slug', 'natural')
                ->count(),
        );
    }

    public function test_admin_can_update_attribute_value(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $attribute = ProductAttribute::factory()
            ->create();

        $value = AttributeValue::factory()
            ->create([
                'attribute_id' => $attribute->id,
                'name' => 'Red',
                'slug' => 'red',
                'position' => 0,
                'is_active' => true,
            ]);

        $this->actingAs(
            $admin,
            'admin',
        )
            ->put(
                route(
                    'admin.attributes.values.update',
                    [
                        $attribute,
                        $value,
                    ],
                ),
                [
                    'name' => 'Dark Red',
                    'slug' => '',
                    'position' => 5,
                    'is_active' => false,
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'attribute_values',
            [
                'id' => $value->id,
                'attribute_id' => $attribute->id,
                'name' => 'Dark Red',
                'slug' => 'dark-red',
                'position' => 5,
                'is_active' => false,
            ],
        );
    }

    public function test_updating_value_preserves_scoped_slug_uniqueness(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $attribute = ProductAttribute::factory()
            ->create();

        AttributeValue::factory()
            ->create([
                'attribute_id' => $attribute->id,
                'name' => 'Red',
                'slug' => 'red',
            ]);

        $value = AttributeValue::factory()
            ->create([
                'attribute_id' => $attribute->id,
                'name' => 'Blue',
                'slug' => 'blue',
            ]);

        $this->actingAs(
            $admin,
            'admin',
        )
            ->put(
                route(
                    'admin.attributes.values.update',
                    [
                        $attribute,
                        $value,
                    ],
                ),
                [
                    'name' => 'Changed Red',
                    'slug' => 'red',
                    'position' => 0,
                    'is_active' => true,
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'attribute_values',
            [
                'id' => $value->id,
                'slug' => 'red-2',
            ],
        );
    }

    public function test_value_cannot_be_updated_through_another_attribute(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $first = ProductAttribute::factory()
            ->create();

        $second = ProductAttribute::factory()
            ->create();

        $value = AttributeValue::factory()
            ->create([
                'attribute_id' => $first->id,
            ]);

        $this->actingAs(
            $admin,
            'admin',
        )
            ->put(
                route(
                    'admin.attributes.values.update',
                    [
                        $second,
                        $value,
                    ],
                ),
                [
                    'name' => 'Changed',
                    'slug' => '',
                    'position' => 0,
                    'is_active' => true,
                ],
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'attribute_values',
            [
                'id' => $value->id,
                'attribute_id' => $first->id,
            ],
        );
    }

    public function test_value_cannot_be_deleted_through_another_attribute(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $first = ProductAttribute::factory()
            ->create();

        $second = ProductAttribute::factory()
            ->create();

        $value = AttributeValue::factory()
            ->create([
                'attribute_id' => $first->id,
            ]);

        $this->actingAs(
            $admin,
            'admin',
        )
            ->delete(
                route(
                    'admin.attributes.values.destroy',
                    [
                        $second,
                        $value,
                    ],
                ),
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'attribute_values',
            [
                'id' => $value->id,
            ],
        );
    }

    public function test_editor_cannot_delete_attribute_value(): void
    {
        $editor = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $attribute = ProductAttribute::factory()
            ->create();

        $value = AttributeValue::factory()
            ->create([
                'attribute_id' => $attribute->id,
            ]);

        $this->actingAs(
            $editor,
            'admin',
        )
            ->delete(
                route(
                    'admin.attributes.values.destroy',
                    [
                        $attribute,
                        $value,
                    ],
                ),
            )
            ->assertForbidden();

        $this->assertDatabaseHas(
            'attribute_values',
            [
                'id' => $value->id,
            ],
        );
    }

    public function test_manager_can_delete_attribute_value(): void
    {
        $manager = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $attribute = ProductAttribute::factory()
            ->create();

        $value = AttributeValue::factory()
            ->create([
                'attribute_id' => $attribute->id,
            ]);

        $this->actingAs(
            $manager,
            'admin',
        )
            ->delete(
                route(
                    'admin.attributes.values.destroy',
                    [
                        $attribute,
                        $value,
                    ],
                ),
            )
            ->assertRedirect();

        $this->assertDatabaseMissing(
            'attribute_values',
            [
                'id' => $value->id,
            ],
        );
    }

    public function test_deleting_attribute_cascades_its_values(): void
    {
        $manager = Admin::factory()->create([
            'role' => AdminRole::Manager,
        ]);

        $attribute = ProductAttribute::factory()
            ->create();

        $value = AttributeValue::factory()
            ->create([
                'attribute_id' => $attribute->id,
            ]);

        $this->actingAs(
            $manager,
            'admin',
        )
            ->delete(
                route(
                    'admin.attributes.destroy',
                    $attribute,
                ),
            )
            ->assertRedirect(
                route('admin.attributes.index'),
            );

        $this->assertDatabaseMissing(
            'attributes',
            [
                'id' => $attribute->id,
            ],
        );

        $this->assertDatabaseMissing(
            'attribute_values',
            [
                'id' => $value->id,
            ],
        );
    }

    public function test_attribute_index_can_search_by_name(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        ProductAttribute::factory()
            ->create([
                'name' => 'Color',
                'slug' => 'color',
            ]);

        ProductAttribute::factory()
            ->create([
                'name' => 'Size',
                'slug' => 'size',
            ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.attributes.index',
                    [
                        'search' => 'Color',
                    ],
                ),
            );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->component(
                    'Admin/Catalog/Attributes/Index',
                )
                ->has('attributes.data', 1)
                ->where(
                    'attributes.data.0.name',
                    'Color',
                ),
        );
    }

    public function test_attribute_index_can_filter_active_attributes(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        ProductAttribute::factory()
            ->create([
                'name' => 'Active Attribute',
                'slug' => 'active-attribute',
                'is_active' => true,
            ]);

        ProductAttribute::factory()
            ->create([
                'name' => 'Inactive Attribute',
                'slug' => 'inactive-attribute',
                'is_active' => false,
            ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.attributes.index',
                    [
                        'status' => 'active',
                    ],
                ),
            );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->component(
                    'Admin/Catalog/Attributes/Index',
                )
                ->has('attributes.data', 1)
                ->where(
                    'attributes.data.0.name',
                    'Active Attribute',
                ),
        );
    }

    public function test_attribute_index_can_filter_inactive_attributes(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        ProductAttribute::factory()
            ->create([
                'name' => 'Active Attribute',
                'slug' => 'active-attribute',
                'is_active' => true,
            ]);

        ProductAttribute::factory()
            ->create([
                'name' => 'Inactive Attribute',
                'slug' => 'inactive-attribute',
                'is_active' => false,
            ]);

        $response = $this
            ->actingAs(
                $admin,
                'admin',
            )
            ->get(
                route(
                    'admin.attributes.index',
                    [
                        'status' => 'inactive',
                    ],
                ),
            );

        $response->assertOk();

        $response->assertInertia(
            fn ($page) => $page
                ->component(
                    'Admin/Catalog/Attributes/Index',
                )
                ->has('attributes.data', 1)
                ->where(
                    'attributes.data.0.name',
                    'Inactive Attribute',
                ),
        );
    }

    public function test_attribute_validation_rejects_missing_name(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $this->actingAs(
            $admin,
            'admin',
        )
            ->post(
                route('admin.attributes.store'),
                [
                    'name' => '',
                    'slug' => '',
                    'position' => 0,
                    'is_active' => true,
                ],
            )
            ->assertSessionHasErrors([
                'name',
            ]);
    }

    public function test_attribute_value_validation_rejects_missing_name(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $attribute = ProductAttribute::factory()
            ->create();

        $this->actingAs(
            $admin,
            'admin',
        )
            ->post(
                route(
                    'admin.attributes.values.store',
                    $attribute,
                ),
                [
                    'name' => '',
                    'slug' => '',
                    'position' => 0,
                    'is_active' => true,
                ],
            )
            ->assertSessionHasErrors([
                'name',
            ]);
    }
}
