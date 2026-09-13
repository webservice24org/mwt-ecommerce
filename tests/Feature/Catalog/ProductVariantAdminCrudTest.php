<?php

declare(strict_types=1);

namespace Tests\Feature\Catalog;

use App\Domain\Auth\Admin\Enums\AdminRole;
use App\Models\Admin;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductVariantAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product_variant(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $product = Product::factory()->create();

        $color = ProductAttribute::factory()->create();

        $red = AttributeValue::factory()->create([
            'attribute_id' => $color->id,
            'name' => 'Red',
            'slug' => 'red',
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route(
                    'admin.products.variants.store',
                    $product,
                ),
                [
                    'sku' => 'TSHIRT-RED',
                    'name' => 'Red',
                    'price' => 125000,
                    'compare_at_price' => 150000,
                    'cost_price' => 80000,
                    'barcode' => '123456789',
                    'position' => 0,
                    'is_active' => true,
                    'is_default' => false,
                    'weight' => '0.250',
                    'attribute_value_ids' => [
                        $red->id,
                    ],
                ],
            )
            ->assertRedirect();

        $variant = ProductVariant::query()
            ->where('sku', 'TSHIRT-RED')
            ->firstOrFail();

        $this->assertTrue(
            $variant->is_default,
        );

        $this->assertDatabaseHas(
            'product_variant_values',
            [
                'product_variant_id' => $variant->id,
                'attribute_value_id' => $red->id,
            ],
        );
    }

    public function test_first_variant_is_automatically_default(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $product = Product::factory()->create();

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route(
                    'admin.products.variants.store',
                    $product,
                ),
                [
                    'sku' => 'FIRST',
                    'price' => 10000,
                    'position' => 0,
                    'is_active' => true,
                    'is_default' => false,
                    'attribute_value_ids' => [],
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'product_variants',
            [
                'product_id' => $product->id,
                'sku' => 'FIRST',
                'is_default' => true,
            ],
        );
    }

    public function test_sku_must_be_globally_unique(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        ProductVariant::factory()->create([
            'sku' => 'UNIQUE-SKU',
        ]);

        $product = Product::factory()->create();

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route(
                    'admin.products.variants.store',
                    $product,
                ),
                [
                    'sku' => 'UNIQUE-SKU',
                    'price' => 10000,
                    'position' => 0,
                    'is_active' => true,
                    'is_default' => false,
                    'attribute_value_ids' => [],
                ],
            )
            ->assertSessionHasErrors('sku');
    }

    public function test_barcode_must_be_unique_when_present(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        ProductVariant::factory()->create([
            'barcode' => 'ABC123',
        ]);

        $product = Product::factory()->create();

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route(
                    'admin.products.variants.store',
                    $product,
                ),
                [
                    'sku' => 'SKU-2',
                    'price' => 10000,
                    'barcode' => 'ABC123',
                    'position' => 0,
                    'is_active' => true,
                    'is_default' => false,
                    'attribute_value_ids' => [],
                ],
            )
            ->assertSessionHasErrors('barcode');
    }

    public function test_compare_at_price_cannot_be_lower_than_price(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $product = Product::factory()->create();

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route(
                    'admin.products.variants.store',
                    $product,
                ),
                [
                    'sku' => 'PRICE-TEST',
                    'price' => 10000,
                    'compare_at_price' => 9000,
                    'position' => 0,
                    'is_active' => true,
                    'is_default' => false,
                    'attribute_value_ids' => [],
                ],
            )
            ->assertSessionHasErrors(
                'compare_at_price',
            );
    }

    public function test_variant_cannot_have_two_values_from_same_attribute(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $product = Product::factory()->create();

        $color = ProductAttribute::factory()->create();

        $red = AttributeValue::factory()->create([
            'attribute_id' => $color->id,
        ]);

        $blue = AttributeValue::factory()->create([
            'attribute_id' => $color->id,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route(
                    'admin.products.variants.store',
                    $product,
                ),
                [
                    'sku' => 'INVALID-COLOR',
                    'price' => 10000,
                    'position' => 0,
                    'is_active' => true,
                    'is_default' => false,
                    'attribute_value_ids' => [
                        $red->id,
                        $blue->id,
                    ],
                ],
            )
            ->assertSessionHasErrors(
                'attribute_value_ids',
            );

        $this->assertDatabaseMissing(
            'product_variants',
            [
                'sku' => 'INVALID-COLOR',
            ],
        );
    }

    public function test_exact_attribute_combination_must_be_unique_per_product(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $product = Product::factory()->create();

        $color = ProductAttribute::factory()->create();

        $size = ProductAttribute::factory()->create();

        $red = AttributeValue::factory()->create([
            'attribute_id' => $color->id,
        ]);

        $medium = AttributeValue::factory()->create([
            'attribute_id' => $size->id,
        ]);

        $existing = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        $existing
            ->attributeValues()
            ->sync([
                $red->id,
                $medium->id,
            ]);

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route(
                    'admin.products.variants.store',
                    $product,
                ),
                [
                    'sku' => 'DUP-COMBINATION',
                    'price' => 10000,
                    'position' => 0,
                    'is_active' => true,
                    'is_default' => false,
                    'attribute_value_ids' => [
                        $medium->id,
                        $red->id,
                    ],
                ],
            )
            ->assertSessionHasErrors(
                'attribute_value_ids',
            );
    }

    public function test_same_combination_can_exist_on_different_products(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $firstProduct =
            Product::factory()->create();

        $secondProduct =
            Product::factory()->create();

        $color = ProductAttribute::factory()->create();

        $red = AttributeValue::factory()->create([
            'attribute_id' => $color->id,
        ]);

        $firstVariant =
            ProductVariant::factory()->create([
                'product_id' => $firstProduct->id,
            ]);

        $firstVariant
            ->attributeValues()
            ->sync([$red->id]);

        $this
            ->actingAs($admin, 'admin')
            ->post(
                route(
                    'admin.products.variants.store',
                    $secondProduct,
                ),
                [
                    'sku' => 'SECOND-PRODUCT-RED',
                    'price' => 10000,
                    'position' => 0,
                    'is_active' => true,
                    'is_default' => false,
                    'attribute_value_ids' => [
                        $red->id,
                    ],
                ],
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'product_variants',
            [
                'product_id' => $secondProduct->id,
                'sku' => 'SECOND-PRODUCT-RED',
            ],
        );
    }

    public function test_setting_new_default_clears_old_default(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $product = Product::factory()->create();

        $first = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'FIRST',
            'is_default' => true,
        ]);

        $second = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'SECOND',
            'is_default' => false,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->put(
                route(
                    'admin.products.variants.update',
                    [
                        $product,
                        $second,
                    ],
                ),
                [
                    'sku' => 'SECOND',
                    'price' => $second->price,
                    'position' => 1,
                    'is_active' => true,
                    'is_default' => true,
                    'attribute_value_ids' => [],
                ],
            )
            ->assertRedirect();

        $this->assertFalse(
            (bool) $first
                ->refresh()
                ->is_default,
        );

        $this->assertTrue(
            (bool) $second
                ->refresh()
                ->is_default,
        );
    }

    public function test_default_cannot_be_unset_while_other_variants_exist(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $product = Product::factory()->create();

        $default = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'DEFAULT',
            'is_default' => true,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $product->id,
            'sku' => 'OTHER',
            'is_default' => false,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->put(
                route(
                    'admin.products.variants.update',
                    [
                        $product,
                        $default,
                    ],
                ),
                [
                    'sku' => 'DEFAULT',
                    'price' => $default->price,
                    'position' => 0,
                    'is_active' => true,
                    'is_default' => false,
                    'attribute_value_ids' => [],
                ],
            )
            ->assertSessionHasErrors(
                'is_default',
            );

        $this->assertTrue(
            (bool) $default
                ->refresh()
                ->is_default,
        );
    }

    public function test_deleting_default_promotes_another_variant(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $product = Product::factory()->create();

        $default = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_default' => true,
            'position' => 0,
        ]);

        $replacement =
            ProductVariant::factory()->create([
                'product_id' => $product->id,
                'is_default' => false,
                'is_active' => true,
                'position' => 1,
            ]);

        $this
            ->actingAs($admin, 'admin')
            ->delete(
                route(
                    'admin.products.variants.destroy',
                    [
                        $product,
                        $default,
                    ],
                ),
            )
            ->assertRedirect();

        $this->assertDatabaseMissing(
            'product_variants',
            [
                'id' => $default->id,
            ],
        );

        $this->assertTrue(
            (bool) $replacement
                ->refresh()
                ->is_default,
        );
    }

    public function test_variant_cannot_be_updated_through_wrong_product(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $firstProduct =
            Product::factory()->create();

        $secondProduct =
            Product::factory()->create();

        $variant = ProductVariant::factory()->create([
            'product_id' => $firstProduct->id,
        ]);

        $this
            ->actingAs($admin, 'admin')
            ->put(
                route(
                    'admin.products.variants.update',
                    [
                        $secondProduct,
                        $variant,
                    ],
                ),
                [
                    'sku' => $variant->sku,
                    'price' => $variant->price,
                    'position' => 0,
                    'is_active' => true,
                    'is_default' => true,
                    'attribute_value_ids' => [],
                ],
            )
            ->assertNotFound();
    }

    public function test_editor_cannot_delete_variant(): void
    {
        $editor = Admin::factory()->create([
            'role' => AdminRole::Editor,
        ]);

        $product = Product::factory()->create();

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
        ]);

        $this
            ->actingAs($editor, 'admin')
            ->delete(
                route(
                    'admin.products.variants.destroy',
                    [
                        $product,
                        $variant,
                    ],
                ),
            )
            ->assertForbidden();
    }

    public function test_used_attribute_value_cannot_be_deleted(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $attribute =
            ProductAttribute::factory()->create();

        $value = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
        ]);

        $variant =
            ProductVariant::factory()->create();

        $variant
            ->attributeValues()
            ->sync([$value->id]);

        $this
            ->actingAs($admin, 'admin')
            ->delete(
                route(
                    'admin.attributes.values.destroy',
                    [
                        $attribute,
                        $value,
                    ],
                ),
            )
            ->assertSessionHasErrors(
                'attribute_value',
            );

        $this->assertDatabaseHas(
            'attribute_values',
            [
                'id' => $value->id,
            ],
        );
    }

    public function test_used_attribute_cannot_be_deleted(): void
    {
        $admin = Admin::factory()->create([
            'role' => AdminRole::Admin,
        ]);

        $attribute =
            ProductAttribute::factory()->create();

        $value = AttributeValue::factory()->create([
            'attribute_id' => $attribute->id,
        ]);

        $variant =
            ProductVariant::factory()->create();

        $variant
            ->attributeValues()
            ->sync([$value->id]);

        $this
            ->actingAs($admin, 'admin')
            ->delete(
                route(
                    'admin.attributes.destroy',
                    $attribute,
                ),
            )
            ->assertSessionHasErrors(
                'attribute',
            );

        $this->assertDatabaseHas(
            'attributes',
            [
                'id' => $attribute->id,
            ],
        );
    }
}
